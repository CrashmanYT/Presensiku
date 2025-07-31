<?php

namespace App\Http\Controllers\Api;

use App\Contracts\DeviceRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\WebhookAttendanceRequest;
use App\Models\Device;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentLeaveRequest;
use App\Models\Teacher;
use App\Models\TeacherLeaveRequest;
use App\Services\AttendanceProcessingService;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    protected UserRepositoryInterface $userRepository;
    protected DeviceRepositoryInterface $deviceRepository;
    protected AttendanceProcessingService $attendanceProcessingService;

    public function __construct(
        UserRepositoryInterface $userRepository,
        DeviceRepositoryInterface $deviceRepository,
        AttendanceProcessingService $attendanceProcessingService
    ) {
        $this->userRepository = $userRepository;
        $this->deviceRepository = $deviceRepository;
        $this->attendanceProcessingService = $attendanceProcessingService;
    }

    /**
     * Handle attendance data from fingerprint device.
     * This endpoint receives real-time data from fingerprint scanners.
     */
    public function handleAttendance(WebhookAttendanceRequest $request): JsonResponse
    {
        $fingerprintId = $request->getFingerprintId();
        $scanDateTime = Carbon::parse($request->getScanTime());
        $device = $this->deviceRepository->getOrCreateByCloudId($request->getCloudId());

        $user = $this->userRepository->findByFingerprintId($fingerprintId);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
        }

        if (!$user->class_id && $user instanceof Student) {
            return response()->json(['status' => 'error', 'message' => 'Siswa tidak memiliki kelas'], 400);
        }

        return $this->attendanceProcessingService->handleScan($user, $device, $scanDateTime);
    }

    public function handleStudentLeaveRequest(Request $request): JsonResponse {
        $secretToken = $request->header('X-Webhook-Secret');
        if ($secretToken !== config('services.webhook.secret_token')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $validator = \Validator::make($request->all(), [
            'identifier' => 'required|string|max:255',
            'start_date' => 'required|date_format:Y-m-d',
            'type' => 'required|string|in:Sakit,Izin',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'reason' => 'required|string',
            'attachment_url' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $validated = $validator->validated();
        $identifier = trim($validated['identifier']);
        $newStartDate = Carbon::parse($validated['start_date']);
        $newEndDate = Carbon::parse($validated['end_date']);

        try {
            DB::beginTransaction();

            $student = Student::where('nis', $identifier)->firstOrFail();

            $overlappingRequests = StudentLeaveRequest::where('student_id', $student->id)
                ->where('start_date', '<=', $newStartDate)
                ->where('end_date', '>=', $newEndDate)
                ->get();

            foreach ($overlappingRequests as $oldRequest) {
                $oldStartDate = Carbon::parse($oldRequest->start_date);
                $oldEndDate = Carbon::parse($oldRequest->end_date);

                // Kasus 1: Permintaan baru menelan sepenuhnya permintaan lama -> Hapus yang lama
                if ($newStartDate->lte($oldStartDate) && $newEndDate->gte($oldEndDate)) {
                    $oldRequest->delete();
                    continue;
                }

                // Kasus 2: Permintaan baru memotong bagian akhir dari yang lam
                if ($newStartDate->gt($oldStartDate) && $newStartDate->lte($oldEndDate)) {
                    $oldRequest->end_date = $newStartDate->copy()->subDay()->toDateString();
                }

                if ($newEndDate->gte($oldStartDate) && $newEndDate->lt($oldEndDate)) {
                    $oldRequest->start_date = $newEndDate->copy()->addDay()->toDateString();
                }
                $oldRequest->save();
            }
            
            $leaveRequest = StudentLeaveRequest::create([
                'student_id' => $student->id,
                'type' => strtolower($validated['type']),
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'reason' => $validated['reason'],
                'submitted_by' => $student->name,
                'attachment' => $validated['attachment_url'] ?? null,
                'via' => 'form_online',
            ]);

            $currentDate = $newStartDate->copy();
            while ($currentDate->lte($newEndDate)) {
                StudentAttendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'date' => $currentDate->toDateString(),
                    ],
                    [
                        'status' => strtolower($validated['type']),
                        'time_in' => null,
                        'time_out' => null,
                        'photo_in' => null,
                    ]
                );
                $currentDate->addDay();
            }

            DB::commit();
            return response()->json(['message' => 'Izin Siswa Berhasil Dibuat!','data' => $leaveRequest, 200]);

        } catch (ModelNotFoundException) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
    public function handleTeacherLeaveRequest(Request $request): JsonResponse {
        $secretToken = $request->header('X-Webhook-Secret');
        if ($secretToken !== config('services.webhook.secret_token')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $validator = \Validator::make($request->all(), [
            'identifier' => 'required|string|max:255',
            'start_date' => 'required|date_format:Y-m-d',
            'type' => 'required|string|in:Sakit,Izin',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'reason' => 'required|string',
            'attachment_url' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $validated = $validator->validated();
        $identifier = trim($validated['identifier']);

        try {
            $student = Teacher::where('nip', $identifier)->firstOrFail();

            $leaveRequest = TeacherLeaveRequest::create([
                'teacher_id' => $student->id,
                'type' => strtolower($validated['type']),
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'reason' => $validated['reason'],
                'submitted_by' => $student->name,
                'attachment' => $validated['attachment_url'] ?? null,
                'via' => 'form_online',
            ]);

            return response()->json(['message' => 'Izin Guru Berhasil Dibuat!','data' => $leaveRequest, 200]);

        } catch (ModelNotFoundException) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}

