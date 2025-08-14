<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatusEnum;
use App\Events\StudentAttendanceUpdated;
use App\Contracts\SettingsRepositoryInterface;
use App\Models\StudentAttendance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use App\Support\TimeGate;

/**
 * Find all students marked as absent today and dispatch notifications.
 *
 * Optionally time-gates execution unless forced via --force.
 */
class SendAbsentNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:send-absent-notifications {--force : Force execution regardless of time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find all students marked as absent for the day and trigger a notification for them.';

    /**
     * Execute the console command.
     *
     * Side effects:
     * - Reads today's `student_attendances` with status TIDAK_HADIR
     * - Dispatches `StudentAttendanceUpdated` events per record
     * - Writes info/error logs
     *
     * @return void
     */
    public function handle(SettingsRepositoryInterface $settings): void
    {
        // Centralized time gate (±1 minute window) with --force override
        $force = (bool) $this->option('force');
        $absentNotificationTime = (string) $settings->get('notifications.absent.notification_time', '09:00');
        $timeGate = app(TimeGate::class);
        if (! $timeGate->shouldRunNow(CarbonImmutable::now(), $absentNotificationTime, $force, 1)) {
            Log::info('SendAbsentNotifications skipped: outside time window', [
                'now' => CarbonImmutable::now()->toDateTimeString(),
                'window' => $timeGate->windowMessage($absentNotificationTime, 1),
                'force' => $force,
            ]);
            return; // Skip if not in time window
        }
        
        $this->info('Starting to process absent students for notification...');
        Log::info('Running SendAbsentNotifications command.');

        $today = CarbonImmutable::now()->toDateString();

        $absentAttendances = StudentAttendance::query()
            ->where('date', $today)
            ->where('status', AttendanceStatusEnum::TIDAK_HADIR)
            // Optional: Add a check to prevent re-notifying if you add a 'notification_sent_at' column in the future
            // ->whereNull('notification_sent_at') 
            ->with('student') // Eager load student to prevent N+1 queries
            ->get();

        if ($absentAttendances->isEmpty()) {
            $this->info('No absent students found for today. Nothing to do.');
            Log::info('No absent students found for today.');
            return;
        }

        $this->info("Found {$absentAttendances->count()} absent students. Dispatching events...");

        foreach ($absentAttendances as $attendance) {
            try {
                // Dispatch the event to reuse the existing notification logic
                StudentAttendanceUpdated::dispatch($attendance);
                Log::info("Dispatched StudentAttendanceUpdated event for attendance ID: {$attendance->id}");

                // Optional: Mark as notified if you add the column
                // $attendance->update(['notification_sent_at' => now()]);

            } catch (\Exception $e) {
                Log::error("Failed to dispatch notification for attendance ID: {$attendance->id}", [
                    'error' => $e->getMessage(),
                ]);
                $this->error("Failed to process student: {$attendance->student->name} (ID: {$attendance->student_id})");
            }
        }

        $this->info('Finished processing absent students.');
        Log::info('Finished SendAbsentNotifications command.');
    }
}
