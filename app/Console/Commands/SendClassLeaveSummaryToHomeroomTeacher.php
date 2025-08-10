<?php

namespace App\Console\Commands;

use App\Helpers\SettingsHelper;
use App\Models\StudentAttendance;
use App\Services\WhatsappService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendClassLeaveSummaryToHomeroomTeacher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:send-leave-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a daily summary of students with leave (sick/permit) to their respective homeroom teachers.';

    /**
     * Execute the console command.
     */
    public function handle(WhatsappService $whatsappService)
    {
        // Check if it's the right time to run this command
        $timeInEnd = SettingsHelper::get('attendance.defaults.time_in_end', '08:00');
        $targetTime = \Carbon\Carbon::parse($timeInEnd);
        $currentTime = now();
        
        // Only run if we're within 1 minute of the target time
        if (abs($currentTime->diffInMinutes($targetTime)) > 1) {
            return; // Exit silently if it's not time yet
        }
        
        $this->info('Starting to process daily leave summaries for homeroom teachers...');
        Log::info('Running SendClassLeaveSummaryToHomeroomTeacher command.');

        $today = now()->toDateString();

        // 1. Get all sick or permit attendances for today, grouped by class
        $attendancesByClass = StudentAttendance::where('date', $today)
            ->whereIn('status', ['sakit', 'izin'])
            ->with('student.class.homeroomTeacher')
            ->get()
            ->groupBy('student.class.id');

        if ($attendancesByClass->isEmpty()) {
            $this->info('No students with leave today. Nothing to send.');
            Log::info('SendClassLeaveSummaryToHomeroomTeacher: No students with leave today.');
            return;
        }

        $this->info("Found {$attendancesByClass->count()} classes with students on leave. Processing each class...");

        // 2. Iterate over each class
        foreach ($attendancesByClass as $classId => $attendances) {
            $firstAttendance = $attendances->first();
            $class = $firstAttendance->student->class;

            if (!$class || !$class->homeroomTeacher || !$class->homeroomTeacher->whatsapp_number) {
                Log::warning("Skipping class ID {$classId}: No homeroom teacher or WhatsApp number assigned.");
                continue;
            }

            // 3. Format the message for the homeroom teacher
            $studentList = $attendances->map(function ($att) {
                $status = ucfirst($att->status->value);
                return "- {$att->student->name} ({$status})";
            })->implode("\n");

            $message = sprintf(
                "Laporan Izin/Sakit untuk kelas *%s* hari ini (%s):\n\n%s",
                $class->name,
                now()->translatedFormat('d F Y'),
                $studentList
            );

            // 4. Send the message using WhatsappService
            try {
                $this->info("Sending summary to homeroom teacher of {$class->name}...");
                $whatsappService->sendMessage($class->homeroomTeacher->whatsapp_number, $message);
                Log::info("Successfully sent leave summary to homeroom teacher of class ID {$classId}.");
            } catch (\Exception $e) {
                Log::error("Failed to send WhatsApp summary for class ID: {$classId}", [
                    'error' => $e->getMessage(),
                ]);
                $this->error("Failed to send message for class: {$class->name}");
            }
        }

        $this->info('Finished processing daily leave summaries.');
        Log::info('Finished SendClassLeaveSummaryToHomeroomTeacher command.');
    }
}
