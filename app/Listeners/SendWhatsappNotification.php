<?php

namespace App\Listeners;

use App\Enums\AttendanceStatusEnum;
use App\Events\StudentAttendanceUpdated;
use App\Models\StudentAttendance;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

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

        if ($parentPhoneNumber) {
            $this->whatsappService->sendMessage($parentPhoneNumber, $message);
        } else {
            Log::info("SendWhatsappNotification: Parent phone number not found for student: {$student->name}");
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
        $status = $attendance->status;

        // Tentukan kunci setting berdasarkan status
        $templateKey = 'whatsapp_template_' . $status->value;

        // Ambil template dari database, dengan fallback ke template default jika tidak ditemukan
        $template = Setting::get($templateKey, Setting::get('whatsapp_template_default', ''));

        // Jika template masih kosong, berikan pesan default
        if (empty($template)) {
            return "Pemberitahuan Absensi:\nNama: {$studentName}\nKelas: {$className}\nTanggal: {$date}\nStatus: " . ucfirst(str_replace('_', ' ', $status->value)) . ".\nMohon periksa aplikasi untuk detail lebih lanjut.";
        }

        if ($status === AttendanceStatusEnum::IZIN) {
            return "Izin Atas Nama {$studentName} Berhasil Masuk!";
        }

        // Ganti placeholder
        $message = str_replace(
            ['{student_name}', '{class_name}', '{date}', '{status}'],
            [
                $studentName,
                $className,
                $date,
                ucfirst(str_replace('_', ' ', $status->value)),
            ],
            $template
        );

        return $message;
    }
}
