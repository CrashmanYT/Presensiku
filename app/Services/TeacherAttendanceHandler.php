<?php

namespace App\Services;

use App\Models\AttendanceRule;
use App\Models\Device;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TeacherAttendanceHandler
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Handle teacher attendance logic
     */
    public function handle(Teacher $teacher, Carbon $scanDateTime, AttendanceRule $attendanceRule, Device $device)
    {
        $scanDate = $scanDateTime->format('Y-m-d');
        $attendance = $this->findExistingAttendance($teacher, $scanDate);

        if (!$attendance) {
            return $this->createNewAttendance($teacher, $scanDateTime, $attendanceRule, $device);
        }

        return $this->handleExistingAttendance($teacher, $attendance, $scanDateTime, $attendanceRule);
    }

    private function findExistingAttendance(Teacher $teacher, string $scanDate): ?TeacherAttendance
    {
        return TeacherAttendance::where('teacher_id', $teacher->id)
            ->where('date', $scanDate)
            ->first();
    }

    private function createNewAttendance(Teacher $teacher, Carbon $scanDateTime, AttendanceRule $attendanceRule, Device $device)
    {
        $attendance = new TeacherAttendance();
        $attendance->teacher_id = $teacher->id;
        $attendance->time_in = $scanDateTime;
        $attendance->date = $scanDateTime->format('Y-m-d');
        $attendance->device_id = $device->id;
        $attendance->status = $this->attendanceService->checkAttendanceStatus($scanDateTime, $attendanceRule);
        $attendance->save();

        $this->logAttendanceCreated($teacher, $scanDateTime, $attendance);
        $this->broadcastEvent($teacher);

        return $this->buildSuccessResponse($teacher, $scanDateTime, $attendance, 'Scan masuk berhasil');
    }

    private function handleExistingAttendance(Teacher $teacher, TeacherAttendance $attendance, Carbon $scanDateTime, AttendanceRule $attendanceRule)
    {
        // Check if scanning again before checkout time
        if ($scanDateTime < $attendanceRule->time_out_start) {
            return $this->buildAlreadyCheckedInResponse($teacher, $attendance);
        }

        // Handle checkout
        if (!$attendance->time_out) {
            return $this->processCheckout($teacher, $attendance, $scanDateTime);
        }

        return $this->buildAlreadyCompletedResponse($teacher, $attendance);
    }

    private function processCheckout(Teacher $teacher, TeacherAttendance $attendance, Carbon $scanDateTime)
    {
        $attendance->time_out = $scanDateTime;
        $attendance->save();

        Log::info('Attendance record updated with time_out', [
            'teacher_id' => $teacher->id,
            'date' => $scanDateTime->format('Y-m-d'),
            'time_out' => $scanDateTime->format('H:i:s')
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Scan keluar berhasil',
            'data' => [
                'teacher_name' => $teacher->name,
                'time_in' => $attendance->time_in->format('H:i:s'),
                'time_out' => $scanDateTime->format('H:i:s'),
                'status' => $attendance->status->value,
                'date' => $scanDateTime->format('Y-m-d')
            ]
        ]);
    }

    private function logAttendanceCreated(Teacher $teacher, Carbon $scanDateTime, TeacherAttendance $attendance)
    {
        Log::info('New teacher attendance record created', [
            'teacher_id' => $teacher->id,
            'date' => $scanDateTime->format('Y-m-d'),
            'time_in' => $scanDateTime->format('H:i:s'),
            'status' => $attendance->status->value
        ]);
    }

    private function broadcastEvent(Teacher $teacher)
    {
        broadcast(new \App\Events\UserScanned($teacher->fingerprint_id));
    }

    private function buildSuccessResponse(Teacher $teacher, Carbon $scanDateTime, TeacherAttendance $attendance, string $message)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'teacher_name' => $teacher->name,
                'time_in' => $scanDateTime->format('H:i:s'),
                'status' => $attendance->status->value,
                'date' => $scanDateTime->format('Y-m-d')
            ]
        ]);
    }

    private function buildAlreadyCheckedInResponse(Teacher $teacher, TeacherAttendance $attendance)
    {
        Log::info('Teacher already checked in', [
            'teacher_id' => $teacher->id,
            'date' => $attendance->date,
            'time_in' => $attendance->time_in,
        ]);

        return response()->json([
            'status' => 'info',
            'message' => 'Anda sudah melakukan absensi masuk',
            'data' => [
                'teacher_name' => $teacher->name,
                'time_in' => $attendance->time_in,
                'status' => $attendance->status->value,
            ]
        ]);
    }

    private function buildAlreadyCompletedResponse(Teacher $teacher, TeacherAttendance $attendance)
    {
        return response()->json([
            'status' => 'info',
            'message' => 'Anda sudah melakukan scan masuk dan keluar hari ini',
            'data' => [
                'teacher_name' => $teacher->name,
                'time_in' => $attendance->time_in->format('H:i:s'),
                'time_out' => $attendance->time_out->format('H:i:s'),
                'status' => $attendance->status->value,
                'date' => $attendance->date
            ]
        ]);
    }
}
