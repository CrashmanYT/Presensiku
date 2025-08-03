<?php

namespace App\Http\Controllers\Api;

use App\Contracts\DeviceRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequestWebhookRequest;
use App\Http\Requests\WebhookAttendanceRequest;
use App\Models\Device;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\AttendanceProcessingService;
use App\Services\LeaveRequestService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

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
        $scanDateTime = now();
        $device = $this->deviceRepository->getOrCreateByCloudId($request->getCloudId());

        $user = $this->userRepository->findByFingerprintId($fingerprintId);

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
        }

        if (! $user->class_id && $user instanceof Student) {
            return response()->json(['status' => 'error', 'message' => 'Siswa tidak memiliki kelas'], 400);
        }

        return $this->attendanceProcessingService->handleScan($user, $device, $scanDateTime);
    }

    public function handleStudentLeaveRequest(LeaveRequestWebhookRequest $request, LeaveRequestService $leaveRequestService): JsonResponse
    {
        try {
            $validated = $request->validated();
            $student = Student::where('nis', trim($validated['identifier']))->firstOrFail();

            $leaveRequestService->processFromWebhook($student, $validated);

            return response()->json(['message' => 'Izin Siswa Berhasil Diproses'], 200);
        } catch (ModelNotFoundException $modelNotFoundException) {
            return response()->json(['message' => 'Siswa Dengan NIS Tersebut Tidak Ditemukan'], 404);
        } catch (\Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }

    public function handleTeacherLeaveRequest(LeaveRequestWebhookRequest $request, LeaveRequestService $leaveRequestService): JsonResponse
    {
        try {
            $validated = $request->validated();
            $student = Teacher::where('nip', trim($validated['identifier']))->firstOrFail();

            $leaveRequestService->processFromWebhook($student, $validated);

            return response()->json(['message' => 'Izin Guru Berhasil Diproses'], 200);
        } catch (ModelNotFoundException $modelNotFoundException) {
            return response()->json(['message' => 'Guru Dengan NIP Tersebut Tidak Ditemukan'], 404);
        } catch (\Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }
}
