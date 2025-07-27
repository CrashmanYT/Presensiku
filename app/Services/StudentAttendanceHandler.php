<?php

namespace App\Services;

use App\Models\AttendanceRule;
use App\Models\Device;
use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StudentAttendanceHandler
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Handle student attendance logic
     */
    public function handle(Student $student, Carbon $scanDateTime, AttendanceRule $attendanceRule, Device $device)
    {
        $scanDate = $scanDateTime->format('Y-m-d');
        $attendance = $this->findExistingAttendance($student, $scanDate);

        if (!$attendance) {
            return $this->createNewAttendance($student, $scanDateTime, $attendanceRule, $device);
        }

        return $this->handleExistingAttendance($student, $attendance, $scanDateTime, $attendanceRule);
    }

    private function findExistingAttendance(Student $student, string $scanDate): ?StudentAttendance
    {
        return StudentAttendance::where('student_id', $student->id)
            ->where('date', $scanDate)
            ->first();
    }

    private function createNewAttendance(Student $student, Carbon $scanDateTime, AttendanceRule $attendanceRule, Device $device): \Illuminate\Http\JsonResponse
    {
        $attendance = new StudentAttendance();
        $attendance->student_id = $student->id;
        $attendance->time_in = $scanDateTime;
        $attendance->date = $scanDateTime->format('Y-m-d');
        $attendance->device_id = $device->id;
        $attendance->status = $this->attendanceService->checkAttendanceStatus($scanDateTime, $attendanceRule);
        $attendance->save();

        $this->logAttendanceCreated($student, $scanDateTime, $attendance);
        $this->broadcastEvent($student);

        return $this->buildSuccessResponse($student, $scanDateTime, $attendance, 'Scan masuk berhasil');
    }

    private function handleExistingAttendance(Student $student, StudentAttendance $attendance, Carbon $scanDateTime, AttendanceRule $attendanceRule): \Illuminate\Http\JsonResponse
    {
        // Check if scanning again before checkout time
        if ($scanDateTime < $attendanceRule->time_out_start) {
            return $this->buildAlreadyCheckedInResponse($student, $attendance);
        }

        // Handle checkout
        if (!$attendance->time_out) {
            return $this->processCheckout($student, $attendance, $scanDateTime);
        }

        return $this->buildAlreadyCompletedResponse($student, $attendance);
    }

    private function processCheckout(Student $student, StudentAttendance $attendance, Carbon $scanDateTime): \Illuminate\Http\JsonResponse
    {
        $attendance->time_out = $scanDateTime;
        $attendance->save();

        Log::info('Attendance record updated with time_out', [
            'student_id' => $student->id,
            'date' => $scanDateTime->format('Y-m-d'),
            'time_out' => $scanDateTime->format('H:i:s')
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Scan keluar berhasil',
            'data' => [
                'student_name' => $student->name,
                'time_in' => $attendance->time_in->format('H:i:s'),
                'time_out' => $scanDateTime->format('H:i:s'),
                'status' => $attendance->status->value,
                'date' => $scanDateTime->format('Y-m-d')
            ]
        ]);
    }

    private function logAttendanceCreated(Student $student, Carbon $scanDateTime, StudentAttendance $attendance): void
    {
        Log::info('New attendance record created', [
            'student_id' => $student->id,
            'date' => $scanDateTime->format('Y-m-d'),
            'time_in' => $scanDateTime->format('H:i:s'),
            'status' => $attendance->status->value
        ]);
    }

    private function broadcastEvent(Student $student): void
    {
        broadcast(new \App\Events\UserScanned($student->fingerprint_id));
    }

    private function buildSuccessResponse(Student $student, Carbon $scanDateTime, StudentAttendance $attendance, string $message): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'student_name' => $student->name,
                'time_in' => $scanDateTime->format('H:i:s'),
                'status' => $attendance->status->value,
                'date' => $scanDateTime->format('Y-m-d')
            ]
        ]);
    }

    private function buildAlreadyCheckedInResponse(Student $student, StudentAttendance $attendance): \Illuminate\Http\JsonResponse
    {
        Log::info('Student already checked in', [
            'student_id' => $student->id,
            'date' => $attendance->date,
            'time_in' => $attendance->time_in,
        ]);

        return response()->json([
            'status' => 'info',
            'message' => 'Anda sudah melakukan absensi masuk',
            'data' => [
                'student_name' => $student->name,
                'time_in' => $attendance->time_in,
                'status' => $attendance->status->value,
            ]
        ]);
    }

    private function buildAlreadyCompletedResponse(Student $student, StudentAttendance $attendance): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'info',
            'message' => 'Anda sudah melakukan scan masuk dan keluar hari ini',
            'data' => [
                'student_name' => $student->name,
                'time_in' => $attendance->time_in->format('H:i:s'),
                'time_out' => $attendance->time_out->format('H:i:s'),
                'status' => $attendance->status->value,
                'date' => $attendance->date
            ]
        ]);
    }
}
