<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\DeviceRepositoryInterface;
use App\Services\AttendanceService;
use App\Services\StudentAttendanceHandler;
use App\Services\TeacherAttendanceHandler;
use App\Models\Student;
use App\Models\Teacher;
use App\Http\Requests\WebhookAttendanceRequest;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Helpers\ApiResponseHelper;

class WebhookController extends Controller
{
    protected UserRepositoryInterface $userRepository;
    protected DeviceRepositoryInterface $deviceRepository;
    protected AttendanceService $attendanceService;
    protected StudentAttendanceHandler $studentAttendanceHandler;
    protected TeacherAttendanceHandler $teacherAttendanceHandler;

    public function __construct(
        UserRepositoryInterface $userRepository,
        DeviceRepositoryInterface $deviceRepository,
        AttendanceService $attendanceService,
        StudentAttendanceHandler $studentAttendanceHandler,
        TeacherAttendanceHandler $teacherAttendanceHandler
    ) {
        $this->userRepository = $userRepository;
        $this->deviceRepository = $deviceRepository;
        $this->attendanceService = $attendanceService;
        $this->studentAttendanceHandler = $studentAttendanceHandler;
        $this->teacherAttendanceHandler = $teacherAttendanceHandler;
    }

    /**
     * Handle attendance data from fingerprint device
     * This endpoint receives real-time data from fingerprint scanners
     */
    public function handleAttendance(WebhookAttendanceRequest $request)
    {
        $fingerprintId = $request->getFingerprintId();
        $scanDateTime = Carbon::parse($request->getScanTime());
        $device = $this->deviceRepository->getOrCreateByCloudId($request->getCloudId());

        $user = $this->userRepository->findByFingerprintId($fingerprintId);
        if (!$user) {
            return ApiResponseHelper::notFound('User not found');
        }

        $attendanceRule = $this->attendanceService->getAttendanceRule($user->class_id, $scanDateTime);

        if ($user instanceof Student) {
            return $this->studentAttendanceHandler->handle($user, $scanDateTime, $attendanceRule, $device);
        }

        if ($user instanceof Teacher) {
            return $this->teacherAttendanceHandler->handle($user, $scanDateTime, $attendanceRule, $device);
        }

        return ApiResponseHelper::notFound('User not found');
    }

}
