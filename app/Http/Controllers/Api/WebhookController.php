<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessFingerprintScan;
use App\Models\AttendanceRule;
use App\Models\Device;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\ScanLog;
use App\Enums\EventTypeEnum;
use App\Enums\ScanResultEnum;
use App\Enums\AttendanceStatusEnum;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class WebhookController extends Controller
{
    /**
     * Handle attendance data from fingerprint device
     * This endpoint receives real-time data from fingerprint scanners
     */
    public function handleAttendance(Request $request)
    {
        $validatedData = $request->validate([
            'type' => 'required',
            'cloud_id' => 'required',
            'data.pin' => 'required',
            'data.scan' => 'required',
        ]);
        $fingerprintId = $validatedData['data']['pin'];
        $scanDateTime = Carbon::parse($validatedData['data']['scan']);
        $cloud_id = $validatedData['cloud_id'];

        // get or create device
        $device = $this->getOrCreateDevice($cloud_id);

        // Find student or teacher
        $user = Student::where('fingerprint_id', $fingerprintId)->first();
        if (!$user) $user = Teacher::where('fingerprint_id', $fingerprintId)->first();
        if (!$user) return response()->json(['message' => 'Student not found'], 404);

        // Get the correct attendance rule based on date and day of week
        $attendanceRule = $this->getAttendanceRule($user->class_id, $scanDateTime);

        // Handle Absensi
        if ($user::class == Student::class) {
            return $this->handleStudentAttendance($user, $scanDateTime, $attendanceRule, $device);
        }

        if ($user::class == Teacher::class) {
            return $this->handleTeacherAttendance($user, $scanDateTime, $attendanceRule, $device);
        }

        return response()->json(['message' => 'Student not found'], 404);

    }


    protected function getOrCreateDevice($cloudId) {
        return Device::firstOrCreate(
            ['cloud_id' => $cloudId],
            [
                'name'      => 'Device ' . $cloudId,
                'cloud_id'  => $cloudId,
                'ip_address'=> 'N/A',
                'is_active' => true,
            ]
        );
    }

    /**
     * Get the correct attendance rule based on date and day of week
     */
    protected function getAttendanceRule($classId, Carbon $scanDateTime): ?AttendanceRule
    {
        $scanDate = $scanDateTime->format('Y-m-d');
        $dayName = strtolower($scanDateTime->format('l'));

        Log::info('Getting attendance rule', [
            'class_id' => $classId,
            'scan_date' => $scanDate,
            'day_name' => $dayName
        ]);

        // Priority 1: Check for specific date override
        $dateOverrideRule = AttendanceRule::where('class_id', $classId)
            ->whereDate('date_override', $scanDate)
            ->first();

        if ($dateOverrideRule) {
            Log::info('Found date override rule', [
                'rule_id' => $dateOverrideRule->id,
                'date_override' => $dateOverrideRule->date_override->format('Y-m-d'),
                'time_in_start' => $dateOverrideRule->time_in_start,
                'time_in_end' => $dateOverrideRule->time_in_end
            ]);
            return $dateOverrideRule;
        }

        // Priority 2: Check for day of week rule
        $dayOfWeekRule = AttendanceRule::where('class_id', $classId)
            ->whereNull('date_override')
            ->where(function ($query) use ($dayName) {
                $query->where('day_of_week', 'like', '%"' . $dayName . '"%');
            })
            ->first();

        if ($dayOfWeekRule) {
            Log::info('Found day of week rule', [
                'rule_id' => $dayOfWeekRule->id,
                'day_of_week' => $dayOfWeekRule->day_of_week,
                'time_in_start' => $dayOfWeekRule->time_in_start,
                'time_in_end' => $dayOfWeekRule->time_in_end
            ]);
            return $dayOfWeekRule;
        }

        // Fallback: Get any rule for this class
        $fallbackRule = AttendanceRule::where('class_id', $classId)->first();

        if ($fallbackRule) {
            Log::warning('Using fallback rule', [
                'rule_id' => $fallbackRule->id,
                'class_id' => $classId
            ]);
        } else {
            Log::error('No attendance rule found for class', ['class_id' => $classId]);
        }

        return $fallbackRule;
    }

    public function handleStudentAttendance(Student $student, Carbon $scanDateTime, AttendanceRule $attendanceRule, Device $device ) {
        $scanDate = $scanDateTime->format('Y-m-d');
        $scanTime = $scanDateTime->format('H:i:s');

        // Check if attendance record already exists
        $attendance = StudentAttendance::where('student_id', $student->id)
            ->where('date', $scanDate)
            ->first();

        if (!$attendance) {
            // Create new attendance record (scan in)
            $attendance = new StudentAttendance;
            $attendance->student_id = $student->id;
            $attendance->time_in = $scanDateTime;
            $attendance->date = $scanDate;
            $attendance->device_id = $device->id;
            $attendance->status = $this->checkAttendanceStatus($scanDateTime, $attendanceRule);
            $attendance->save();

            Log::info('New attendance record created', [
                'student_id' => $student->id,
                'date' => $scanDate,
                'time_in' => $scanDateTime->format('H:i:s'),
                'status' => $attendance->status->value
            ]);

            // Dashboard will automatically detect this via polling

            return response()->json([
                'status' => 'success',
                'message' => 'Scan masuk berhasil',
                'data' => [
                    'student_name' => $student->name,
                    'time_in' => $scanDateTime->format('H:i:s'),
                    'status' => $attendance->status->value,
                    'date' => $scanDate
                ]
            ]);
        } else {
            // cek jika absensi di lakukan lagi sebelum jam pulang, maka akan muncul pesan bahwa sudah absen masuk sebelumnya
            if ($scanDateTime < $attendanceRule->time_out_start) {
                Log::info('Anda sudah melakukan absensi masuk', [
                    'student_id' => $student->id,
                    'date' => $scanDate,
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
            // Update existing record (scan out)
            if (!$attendance->time_out) {
                $attendance->time_out = $scanDateTime;
                $attendance->save();

                Log::info('Attendance record updated with time_out', [
                    'student_id' => $student->id,
                    'date' => $scanDate,
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
                        'date' => $scanDate
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => 'info',
                    'message' => 'Anda sudah melakukan scan masuk dan keluar hari ini',
                    'data' => [
                        'student_name' => $student->name,
                        'time_in' => $attendance->time_in->format('H:i:s'),
                        'time_out' => $attendance->time_out->format('H:i:s'),
                        'status' => $attendance->status->value,
                        'date' => $scanDate
                    ]
                ]);
            }
        }
    }
    public function handleTeacherAttendance(Teacher $teacher, Carbon $scanDateTime, AttendanceRule $attendanceRule, Device $device ) {
        $scanDate = $scanDateTime->toDate()->format('Y-m-d');
        $scanTime = $scanDateTime->toDateTime()->format('H:i:s');
        $attendance = StudentAttendance::where('student_id', $teacher->id)
            ->where('date', $scanDate);
        if (!$attendance->exists())
        {
            $attendance = new StudentAttendance;
            $attendance->student_id = $teacher->id;
            $attendance->time_in = $scanTime;
            $attendance->date = $scanDate;
            $attendance->device_id = $device->id;
            $attendance->status = $this->checkAttendanceStatus($scanDateTime, $attendanceRule);
            $attendance->save();
        } else {
            $now = Carbon::now();
            if ($now->between($attendanceRule->time_in_start, $attendanceRule->time_out_start)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Anda telah scan sebelumnya',
                ]);
            }
            $attendance = $attendance->update(['time_out' => $scanTime]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $attendance,
        ]);

    }

    public function checkAttendanceStatus(Carbon $scanTime, AttendanceRule $attendanceRule): AttendanceStatusEnum
    {
        $scanDate = $scanTime->toDateString();
        $dayName = strtolower($scanTime->format('l')); // Get day name in lowercase
        $scanTimeOnly = $scanTime->format('H:i:s'); // Get only time part

        // Extract time parts from datetime fields
        $timeInStart = Carbon::parse($attendanceRule->time_in_start)->format('H:i:s');
        $timeInEnd = Carbon::parse($attendanceRule->time_in_end)->format('H:i:s');

        Log::info('Checking attendance status', [
            'scan_date' => $scanDate,
            'scan_time' => $scanTimeOnly,
            'day_name' => $dayName,
            'time_in_start' => $timeInStart,
            'time_in_end' => $timeInEnd,
            'date_override' => $attendanceRule->date_override,
            'day_of_week' => $attendanceRule->day_of_week
        ]);

        // Check if there's a specific date override

        if ($attendanceRule->date_override && $attendanceRule->date_override->format('Y-m-d') === $scanDate) {
            Log::info('Using date override rule');
            // For date override, check if scan time is within allowed range
            if ($scanTimeOnly >= $timeInStart && $scanTimeOnly <= $timeInEnd) {
                return AttendanceStatusEnum::HADIR;
            } else {
                return AttendanceStatusEnum::TERLAMBAT;
            }
        }

        // Check if current day is in the allowed days of week
        if ($attendanceRule->day_of_week && in_array($dayName, $attendanceRule->day_of_week)) {
            Log::info('Using day of week rule');
            // Check if scan time is within allowed range
            if ($scanTimeOnly >= $timeInStart && $scanTimeOnly <= $timeInEnd) {
                return AttendanceStatusEnum::HADIR;
            } else {
                return AttendanceStatusEnum::TERLAMBAT;
            }
        }

        Log::info('No matching rule found, defaulting to late');
        return AttendanceStatusEnum::TERLAMBAT;
    }




}
