<?php

namespace App\Services;

use App\Models\AttendanceRule;
use App\Models\Device;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AttendanceProcessingService
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Handles the entire attendance scan logic for a user (Student or Teacher).
     */
    public function handleScan(Student|Teacher $user, Device $device, Carbon $scanDateTime): JsonResponse
    {
        $classId = ($user instanceof Student) ? $user->class_id : null;
        $attendanceRule = $this->attendanceService->getAttendanceRule($classId, $scanDateTime);

        $attendance = $this->findExistingAttendance($user, $scanDateTime->toDateString());

        if (!$attendance) {
            return $this->createNewAttendance($user, $scanDateTime, $attendanceRule, $device);
        }

        return $this->handleExistingAttendance($user, $attendance, $scanDateTime, $attendanceRule);
    }

    private function findExistingAttendance(Student|Teacher $user, string $scanDate)
    {
        $model = $user instanceof Student ? StudentAttendance::class : TeacherAttendance::class;
        $foreignKey = $user instanceof Student ? 'student_id' : 'teacher_id';

        return $model::where($foreignKey, $user->id)->where('date', $scanDate)->first();
    }

    private function createNewAttendance(Student|Teacher $user, Carbon $scanDateTime, AttendanceRule $attendanceRule, Device $device): JsonResponse
    {
        $model = $user instanceof Student ? new StudentAttendance() : new TeacherAttendance();
        $foreignKey = $user instanceof Student ? 'student_id' : 'teacher_id';

        $model->{$foreignKey} = $user->id;
        $model->time_in = $scanDateTime;
        $model->date = $scanDateTime->toDateString();
        $model->device_id = $device->id;
        $model->status = $this->attendanceService->checkAttendanceStatus($scanDateTime, $attendanceRule);
        $model->save();

        Log::info('New attendance record created', ['user_id' => $user->id, 'status' => $model->status->value]);
        broadcast(new \App\Events\UserScanned($user->fingerprint_id));

        return $this->buildSuccessResponse($user, $model, 'Scan masuk berhasil');
    }

    private function handleExistingAttendance(Student|Teacher $user, $attendance, Carbon $scanDateTime, AttendanceRule $attendanceRule): JsonResponse
    {
        // Bandingkan hanya bagian waktunya saja untuk menghindari masalah tanggal
        $scanTime = Carbon::parse($scanDateTime->toTimeString());
        $checkoutStartTime = Carbon::parse($attendanceRule->time_out_start);

        if ($scanTime->isBefore($checkoutStartTime)) {
            return $this->buildAlreadyCheckedInResponse($user, $attendance);
        }

        if (!$attendance->time_out) {
            return $this->processCheckout($user, $attendance, $scanDateTime);
        }

        return $this->buildAlreadyCompletedResponse($user, $attendance);
    }

    private function processCheckout(Student|Teacher $user, $attendance, Carbon $scanDateTime): JsonResponse
    {
        $attendance->time_out = $scanDateTime;
        $attendance->save();

        Log::info('Attendance record updated with time_out', ['user_id' => $user->id]);

        return response()->json([
            'status' => 'success',
            'message' => 'Scan keluar berhasil',
            'data' => $this->formatResponseData($user, $attendance)
        ]);
    }

    private function buildSuccessResponse(Student|Teacher $user, $attendance, string $message): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $this->formatResponseData($user, $attendance)
        ]);
    }

    private function buildAlreadyCheckedInResponse(Student|Teacher $user, $attendance): JsonResponse
    {
        Log::info('User already checked in', ['user_id' => $user->id]);
        return response()->json([
            'status' => 'info',
            'message' => 'Anda sudah melakukan absensi masuk',
            'data' => $this->formatResponseData($user, $attendance)
        ], 409);
    }

    private function buildAlreadyCompletedResponse(Student|Teacher $user, $attendance): JsonResponse
    {
        return response()->json([
            'status' => 'info',
            'message' => 'Anda sudah melakukan scan masuk dan keluar hari ini',
            'data' => $this->formatResponseData($user, $attendance)
        ], 409);
    }

    private function formatResponseData(Student|Teacher $user, $attendance): array
    {
        $userNameKey = $user instanceof Student ? 'student_name' : 'teacher_name';
        $data = [
            $userNameKey => $user->name,
            'time_in' => $attendance->time_in ? $attendance->time_in->format('H:i:s') : null,
            'time_out' => $attendance->time_out ? $attendance->time_out->format('H:i:s') : null,
            'status' => $attendance->status->value,
            'date' => $attendance->date,
        ];

        return $data;
    }
}
