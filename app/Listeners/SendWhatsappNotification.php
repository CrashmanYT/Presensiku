<?php

namespace App\Listeners;

use App\Events\StudentAttendanceUpdated;
use App\Models\StudentAttendance;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Contracts\SettingsRepositoryInterface;
use Illuminate\Support\Facades\Log;

class SendWhatsappNotification implements ShouldQueue
{
    protected WhatsappService $whatsappService;
    public function __construct(
        WhatsappService $whatsappService,
        private SettingsRepositoryInterface $settings,
    ) {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Handle the event.
     */
    public function handle(StudentAttendanceUpdated $event): void
    {
        Log::info('SendWhatsappNotification: Handling event for attendance ID: ' . $event->studentAttendance->id);

        $attendance = $event->studentAttendance;
        // Eager-load required relations, including homeroomTeacher if present
        $attendance->load('student.class.homeroomTeacher');

        $student = $attendance->student;
        $class = $student?->class;

        if (!$student) {
            Log::warning("SendWhatsappNotification: Student not found for attendance ID: {$attendance->id}");
            return;
        }

        // Let WhatsappService normalize/validate numbers; do not pre/over-prefix here
        $parentPhoneNumber = $student->parent_whatsapp ?? '';
        $homeroomTeacherPhoneNumber = $class?->homeroomTeacher?->whatsapp_number ?? null;

        $message = $this->buildMessage($attendance);

        if ($parentPhoneNumber && !empty($message)) {
            $this->whatsappService->sendMessage($parentPhoneNumber, $message);
        } else {
            if (empty($message)) {
                Log::info("SendWhatsappNotification: Message is empty, not sending for student: {$student->name}");
            } else {
                Log::info("SendWhatsappNotification: Parent phone number not found for student: {$student->name}");
            }
        }

        // Optionally send to homeroom teacher
        // if ($homeroomTeacherPhoneNumber && !empty($message)) {
        //     $this->whatsappService->sendMessage($homeroomTeacherPhoneNumber, $message);
        // } else {
        //     Log::info("SendWhatsappNotification: Homeroom teacher phone number not found or message empty for class: " . ($class?->name ?? '-'));
        // }
    }

    protected function buildMessage(StudentAttendance $attendance): string
    {
        $studentName = $attendance->student?->name ?? 'Siswa Tidak Dikenal';
        $className = $attendance->student?->class?->name ?? 'Kelas Tidak Dikenal';
        $date = Carbon::parse($attendance->date)->translatedFormat('d F Y');
        $status = $attendance->status; // Enum instance

        // Map attendance status to template type in settings
        $statusMap = [
            'terlambat' => 'late',
            'tidak_hadir' => 'absent',
            'izin' => 'permit',
            'sakit' => 'permit', // 'sakit' uses the 'permit' template.
        ];

        $templateType = $statusMap[$status->value] ?? null;

        // If no template is defined for this status (e.g., 'hadir'), do not send a message.
        if (!$templateType) {
            return '';
        }

        // Handle time_in formatting robustly
        $timeIn = $attendance->time_in;
        if ($timeIn instanceof \Carbon\Carbon) {
            $timeInFormatted = $timeIn->format('H:i');
        } elseif (is_string($timeIn) && $timeIn !== '') {
            try {
                $timeInFormatted = Carbon::parse($timeIn)->format('H:i');
            } catch (\Throwable) {
                $timeInFormatted = '-';
            }
        } else {
            $timeInFormatted = '-';
        }

        // Prepare variables for the template
        $variables = [
            'nama_siswa' => $studentName,
            'kelas' => $className,
            'tanggal' => $date,
            'jam_masuk' => $timeInFormatted,
            'jam_seharusnya' => '-', // Placeholder, as attendance rule is not available here.
        ];

        // Build WhatsApp message from templates stored in settings
        $templates = $this->settings->get('notifications.whatsapp.templates', [
            'late' => [],
            'absent' => [],
            'permit' => [],
        ]);
        if (!is_array($templates) || !isset($templates[$templateType]) || !is_array($templates[$templateType]) || empty($templates[$templateType])) {
            Log::warning("WhatsApp template not found or empty for type: {$templateType}");
            return '';
        }
        $randomTemplate = $templates[$templateType][array_rand($templates[$templateType])];
        $templateString = $randomTemplate['message'] ?? '';
        if ($templateString === '') {
            Log::warning("WhatsApp template invalid for type: {$templateType}");
            return '';
        }
        foreach ($variables as $variable => $value) {
            $templateString = str_replace('{' . $variable . '}', $value, $templateString);
        }
        return $templateString;
    }
}
