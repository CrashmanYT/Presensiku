<?php

namespace App\Http\Controllers\Api;

use App\Contracts\DeviceRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\WebhookAttendanceRequest;
use App\Models\Device;
use App\Models\Student;
use App\Services\AttendanceProcessingService;
use Carbon\Carbon;
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
}

