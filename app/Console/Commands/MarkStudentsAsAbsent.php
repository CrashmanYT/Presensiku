<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatusEnum;
use App\Contracts\SettingsRepositoryInterface;
use App\Models\Holiday;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use App\Support\TimeGate;

/**
 * Mark students as absent if they have no attendance record for today.
 *
 * Skips weekends and configured holidays. Optionally time-gates execution
 * unless forced via --force.
 */
class MarkStudentsAsAbsent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:mark-absent {--force : Force execution regardless of time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for students without any attendance record for the day and marks them as absent.';

    /**
     * Execute the console command.
     *
     * Side effects:
     * - Inserts `student_attendances` records with status TIDAK_HADIR
     * - Writes info/error logs
     *
     * @return void
     */
    public function handle(SettingsRepositoryInterface $settings): void
    {
        // Centralized time gate: run around (absent_notification_time - 5 minutes), unless forced
        $currentTime = CarbonImmutable::now();
        $absentNotificationTime = (string) $settings->get('notifications.absent.notification_time', '09:00');
        [$H, $m] = explode(':', $absentNotificationTime, 2);
        $target = \Carbon\Carbon::create(
            $currentTime->year,
            $currentTime->month,
            $currentTime->day,
            (int) $H,
            (int) $m,
            0,
            $currentTime->getTimezone()
        )->subMinutes(5);
        $timeGate = app(TimeGate::class);
        if (! $timeGate->isWithinWindow($currentTime, $target, (bool) $this->option('force'), 1)) {
            return; // Skip if not within time window
        }
        
        $this->info('Starting to mark absent students...');
        Log::info('Running MarkStudentsAsAbsent command.');

        $today = CarbonImmutable::now();

        // 1. Check if today is a weekend.
        if ($today->isWeekend()) {
            $this->info('Today is a weekend. No action taken.');
            Log::info('MarkStudentsAsAbsent: Today is a weekend. Command skipped.');
            return;
        }

        // 2. Check if today is a holiday.
        $isHoliday = Holiday::where('start_date', '<=', $today->toDateString())
            ->where('end_date', '>=', $today->toDateString())
            ->exists();

        if ($isHoliday) {
            $this->info('Today is a holiday. No action taken.');
            Log::info('MarkStudentsAsAbsent: Today is a holiday. Command skipped.');
            return;
        }

        // 3. Get IDs of students who already have an attendance record today.
        $studentsWithRecordsToday = StudentAttendance::where('date', $today->toDateString())
            ->pluck('student_id')
            ->toArray();

        // 4. Find students who do NOT have a record today.
        $absentStudents = Student::whereNotIn('id', $studentsWithRecordsToday)->get();

        if ($absentStudents->isEmpty()) {
            $this->info('All students have an attendance record for today. Nothing to do.');
            Log::info('MarkStudentsAsAbsent: All students accounted for.');
            return;
        }

        $this->info("Found {$absentStudents->count()} students without an attendance record. Marking them as absent...");

        // 5. Create 'TIDAK_HADIR' records for them one by one to ensure model events are fired.
        foreach ($absentStudents as $student) {
            try {
                StudentAttendance::create([
                    'student_id' => $student->id,
                    'date' => $today->toDateString(),
                    'status' => AttendanceStatusEnum::TIDAK_HADIR,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to create absent record for student ID: {$student->id}", [
                    'error' => $e->getMessage(),
                ]);
                $this->error("Failed to process student: {$student->name} (ID: {$student->id})");
            }
        }

        $this->info('Finished marking absent students.');
        Log::info('Finished MarkStudentsAsAbsent command.');
    }
}
