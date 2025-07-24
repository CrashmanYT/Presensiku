<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\Device;
use App\Models\ScanLog;
use App\Models\StudentAttendance;
use App\Models\AttendanceRule;
use App\Enums\AttendanceStatusEnum;
use App\Enums\ScanResultEnum;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFingerprintScan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Student $student,
        private Carbon $scanTime,
        private string $eventType,
        private Device $device,
        private ScanLog $scanLog
    ) {}

    public function handle(): void
    {
        try {
            Log::info('Processing fingerprint scan', [
                'student_id' => $this->student->id,
                'student_name' => $this->student->name,
                'scan_time' => $this->scanTime,
                'event_type' => $this->eventType,
                'device_id' => $this->device->id
            ]);

            $attendanceDate = $this->scanTime->toDateString();
            
            // Get or create attendance record for this student and date
            $attendance = StudentAttendance::firstOrCreate(
                [
                    'student_id' => $this->student->id,
                    'date' => $attendanceDate,
                ],
                [
                    'status' => AttendanceStatusEnum::TIDAK_HADIR,
                    'device_id' => $this->device->id,
                ]
            );

            // Process based on event type
            if ($this->eventType === 'scan_in') {
                $this->processScanIn($attendance);
            } elseif ($this->eventType === 'scan_out') {
                $this->processScanOut($attendance);
            }

            // Update scan log to success
            $this->scanLog->update(['result' => ScanResultEnum::SUCCESS]);

            Log::info('Fingerprint scan processed successfully', [
                'student_id' => $this->student->id,
                'attendance_id' => $attendance->id,
                'status' => $attendance->status->value,
                'time_in' => $attendance->time_in,
                'time_out' => $attendance->time_out
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing fingerprint scan', [
                'student_id' => $this->student->id,
                'scan_time' => $this->scanTime,
                'event_type' => $this->eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update scan log to failed
            $this->scanLog->update(['result' => ScanResultEnum::FAIL]);

            throw $e;
        }
    }

    private function processScanIn(StudentAttendance $attendance): void
    {
        // If already has time_in, this might be a duplicate scan
        if ($attendance->time_in) {
            Log::warning('Duplicate scan_in detected', [
                'student_id' => $this->student->id,
                'existing_time_in' => $attendance->time_in,
                'new_scan_time' => $this->scanTime
            ]);
            return;
        }

        // Set time_in
        $attendance->time_in = $this->scanTime;
        $attendance->device_id = $this->device->id;

        // Determine attendance status based on time rules
        $status = $this->determineAttendanceStatus($this->scanTime, 'scan_in');
        $attendance->status = $status;

        $attendance->save();
    }

    private function processScanOut(StudentAttendance $attendance): void
    {
        // Set time_out
        $attendance->time_out = $this->scanTime;
        $attendance->save();

        Log::info('Scan out processed', [
            'student_id' => $this->student->id,
            'time_out' => $this->scanTime
        ]);
    }

    private function determineAttendanceStatus(Carbon $scanTime, string $eventType): AttendanceStatusEnum
    {
        // Get attendance rules for the student's class
        $student = $this->student->load('class');
        
        if (!$student->class) {
            Log::warning('Student has no class assigned', ['student_id' => $student->id]);
            return AttendanceStatusEnum::HADIR; // Default to present
        }

        // Get applicable attendance rule
        $rule = AttendanceRule::where('class_id', $student->class->id)
            ->where(function ($query) use ($scanTime) {
                // Check for specific date override
                $query->where('date_override', $scanTime->toDateString())
                    // Or check for day of week rule
                    ->orWhere(function ($q) use ($scanTime) {
                        $q->whereNull('date_override')
                          ->where('day_of_week', 'like', '%"' . $scanTime->dayOfWeek . '"%');
                    });
            })
            ->first();

        if (!$rule) {
            Log::info('No attendance rule found, defaulting to present', [
                'student_id' => $student->id,
                'class_id' => $student->class->id,
                'scan_date' => $scanTime->toDateString(),
                'day_of_week' => $scanTime->dayOfWeek
            ]);
            return AttendanceStatusEnum::HADIR;
        }

        // For scan_in, check if they're late
        if ($eventType === 'scan_in') {
            $scanTimeOnly = $scanTime->format('H:i:s');
            $lateThreshold = $rule->time_in_end; // Assuming time_in_end is the late threshold
            
            if ($scanTimeOnly > $lateThreshold) {
                return AttendanceStatusEnum::TERLAMBAT;
            }
        }

        return AttendanceStatusEnum::HADIR;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessFingerprintScan job failed', [
            'student_id' => $this->student->id,
            'scan_time' => $this->scanTime,
            'event_type' => $this->eventType,
            'error' => $exception->getMessage()
        ]);

        // Update scan log to failed
        $this->scanLog->update(['result' => ScanResultEnum::FAIL]);
    }
}
