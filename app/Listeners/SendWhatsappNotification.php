<?php

namespace App\Listeners;

use App\Enums\AttendanceStatusEnum;
use App\Events\StudentAttendanceUpdated;
use App\Models\StudentAttendance;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\Log;

class SendWhatsappNotification implements ShouldQueue
{
    protected WhatsappService $whatsappService;
    /**
     * Create the event listener.
     * @param WhatsappService $whatsappService
     */
    public function __construct(WhatsappService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Handle the event.
     */
    public function handle(StudentAttendanceUpdated $event): void
    {
        Log::info('SendWhatsappNotification: Handling event for attendance ID: ' . $event->studentAttendance->id);

        $attendance = $event->studentAttendance;
        $student = $attendance->student;
        $class = $student->class;

        $attendance->load('student.class');

        if (!$student) {
            Log::warning("SendWhatsappNotification: Student not found for attendance ID: {$attendance->id}");
            return;
        }

        $parentPhoneNumber = "62".$student->parent_whatsapp;
        $homeroomTeacherPhoneNumber = $class->homeroomTeacher->whatsapp_number;

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

        // if ($homeroomTeacherPhoneNumber) {
        //     $this->whatsappService->sendMessage($homeroomTeacherPhoneNumber, $message);
        // } else {
        //     Log::info("SendWhatsappNotification: Homeroom teacher phone number not found for class: {$class->name}");
        // }

    }

    protected function buildMessage(StudentAttendance $attendance): string
    {
        $studentName = $attendance->student->name ?? 'Siswa Tidak Dikenal';
        $className = $attendance->student->class->name ?? 'Kelas Tidak Dikenal';
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

        // Prepare variables for the template
        $variables = [
            'nama_siswa' => $studentName,
            'kelas' => $className,
            'tanggal' => $date,
            'jam_masuk' => $attendance->time_in ? $attendance->time_in->format('H:i') : '-',
            'jam_seharusnya' => '-', // Placeholder, as attendance rule is not available here.
        ];

        // Get the message from the helper, which handles random template picking and variable replacement
        $message = SettingsHelper::getWhatsAppMessage($templateType, $variables);

        // If the template is not found or empty, the helper returns a default error message.
        // We can check for this and return an empty string to avoid sending error messages to parents.
        if (str_contains($message, 'tidak ditemukan atau kosong')) {
            Log::warning("WhatsApp template not found for type: {$templateType}");
            return '';
        }

        return $message;
    }
}
