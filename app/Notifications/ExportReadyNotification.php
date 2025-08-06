<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ExportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $relativePath;

    public function __construct(string $relativePath)
    {
        // Example: 'exports/rekapitulasi-absensi/2025/08/rekapitulasi-absensi-YYYYmmdd-His-UID.xlsx'
        $this->relativePath = $relativePath;
    }

    public function via(object $notifiable): array
    {
        // Only use the database channel for in-app notifications
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // This mail channel is not used (see via()) but is kept for compatibility
        return (new MailMessage)
            ->subject('File Ekspor Rekapitulasi Absensi Anda Telah Siap')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Proses ekspor rekapitulasi absensi yang Anda minta telah selesai.')
            ->action('Unduh File', $this->getDownloadUrl())
            ->line('Tautan ini akan valid selama 24 jam. Terima kasih telah menggunakan aplikasi kami!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Ekspor Selesai',
            'body' => 'File rekapitulasi absensi Anda siap untuk diunduh.',
            'action_url' => $this->getDownloadUrl(),
            'action_text' => 'Unduh File',
        ];
    }

    protected function getDownloadUrl(): string
    {
        // Build the public URL directly from the public disk
        return Storage::disk('public')->url($this->relativePath);
    }
}