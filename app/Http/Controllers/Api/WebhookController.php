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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

/**
 * API Webhook controller handling real-time attendance scans and leave requests.
 *
 * Endpoints:
 * - handleAttendance: ingest scans from fingerprint devices
 * - handleStudentLeaveRequest: ingest student leave requests from external forms
 * - handleTeacherLeaveRequest: ingest teacher leave requests from external forms
 */
class WebhookController extends Controller
{
    /** @var UserRepositoryInterface */
    protected UserRepositoryInterface $userRepository;

    /** @var DeviceRepositoryInterface */
    protected DeviceRepositoryInterface $deviceRepository;

    /** @var AttendanceProcessingService */
    protected AttendanceProcessingService $attendanceProcessingService;

    /**
     * Inject dependencies.
     *
     * @param UserRepositoryInterface $userRepository
     * @param DeviceRepositoryInterface $deviceRepository
     * @param AttendanceProcessingService $attendanceProcessingService
     */
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
     *
     * Flow:
     * - Resolve device by cloud id (create if not exists)
     * - Find user by fingerprint id
     * - Validate student has class assignment
     * - Delegate scan handling to AttendanceProcessingService
     *
     * @param WebhookAttendanceRequest $request
     * @return JsonResponse
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

    /**
     * Handle student leave requests submitted via external webhook (e.g., Google Forms).
     *
     * @param LeaveRequestWebhookRequest $request Validated request containing identifier and leave details
     * @param LeaveRequestService $leaveRequestService
     * @return JsonResponse
     */
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

    /**
     * Handle teacher leave requests submitted via external webhook (e.g., Google Forms).
     *
     * @param LeaveRequestWebhookRequest $request Validated request containing identifier and leave details
     * @param LeaveRequestService $leaveRequestService
     * @return JsonResponse
     */
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

