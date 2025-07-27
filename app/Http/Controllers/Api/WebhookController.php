<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRule;
use App\Models\Device;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\ScanLog;
use App\Enums\EventTypeEnum;
use App\Enums\ScanResultEnum;
use App\Enums\AttendanceStatusEnum;
use App\Models\Teacher;
use App\Services\AttendanceService;
use App\Services\StudentAttendanceHandler;
use App\Services\TeacherAttendanceHandler;
use App\Models\TeacherAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class WebhookController extends Controller
{
    protected AttendanceService $attendanceService;
    protected StudentAttendanceHandler $studentAttendanceHandler;
    protected TeacherAttendanceHandler $teacherAttendanceHandler;

    public function __construct(
        AttendanceService $attendanceService,
        StudentAttendanceHandler $studentAttendanceHandler,
        TeacherAttendanceHandler $teacherAttendanceHandler
    ) {
        $this->attendanceService = $attendanceService;
        $this->studentAttendanceHandler = $studentAttendanceHandler;
        $this->teacherAttendanceHandler = $teacherAttendanceHandler;
    }

    /**
     * Handle attendance data from fingerprint device
     * This endpoint receives real-time data from fingerprint scanners
     */
    public function handleAttendance(Request $request)
    {
        $validatedData = $this->validateRequest($request);
        $fingerprintId = $validatedData['data']['pin'];
        $scanDateTime = $this->getScanTime($validatedData['data']['scan']);
        $device = $this->attendanceService->getOrCreateDevice($validatedData['cloud_id']);

        $user = $this->attendanceService->findUserByFingerprintId($fingerprintId);
        if (!$user) {
            return $this->respondNotFound('User not found');
        }

        $attendanceRule = $this->attendanceService->getAttendanceRule($user->class_id, $scanDateTime);

        if ($user instanceof Student) {
            return $this->studentAttendanceHandler->handle($user, $scanDateTime, $attendanceRule, $device);
        }

        if ($user instanceof Teacher) {
            return $this->teacherAttendanceHandler->handle($user, $scanDateTime, $attendanceRule, $device);
        }

        return $this->respondNotFound('User not found');
    }

    protected function validateRequest(Request $request)
    {
        return $request->validate([
            'type' => 'required',
            'cloud_id' => 'required',
            'data.pin' => 'required',
            'data.scan' => 'required',
        ]);
    }

    protected function getScanTime(string $scanTimeString)
    {
        return Carbon::parse($scanTimeString);
    }

    protected function findUserByFingerprintId(string $fingerprintId)
    {
        return Student::where('fingerprint_id', $fingerprintId)->first()
            ?? Teacher::where('fingerprint_id', $fingerprintId)->first();
    }

    protected function respondNotFound(string $message)
    {
        return response()->json(['message' => $message], 404);
    }

}
