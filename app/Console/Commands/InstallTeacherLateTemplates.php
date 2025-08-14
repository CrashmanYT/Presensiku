<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;

class InstallTeacherLateTemplates extends Command
{
    protected $signature = 'settings:install-teacher-late-templates {--overwrite : Overwrite existing values if present}';

    protected $description = 'Install default WhatsApp templates for daily teacher-late report into settings.';

    public function handle(): int
    {
        $overwrite = (bool) $this->option('overwrite');

        $payloads = [
            'notifications.whatsapp.templates.report_teacher_late_no_data' => [
                ['message' => "Laporan Guru Terlambat — {date_title}\n\nTidak ada guru yang terlambat hari ini."],
            ],
            'notifications.whatsapp.templates.report_teacher_late_pdf_link' => [
                ['message' => "Laporan Guru Terlambat — {date_title}\n\nUnduh PDF: {pdf_url}"],
            ],
            'notifications.whatsapp.templates.report_teacher_late_pdf_attachment_caption' => [
                ['message' => "Laporan Guru Terlambat — {date_title}"],
            ],
            'notifications.whatsapp.templates.report_teacher_late_text' => [
                ['message' => "Laporan Guru Terlambat — {date_title}\n\n{list}"],
            ],
        ];

        foreach ($payloads as $key => $value) {
            if (!$overwrite && Setting::has($key)) {
                $this->line("Skip (exists): {$key}");
                continue;
            }
            Setting::set($key, $value, 'json', 'notifications');
            $this->info(($overwrite ? 'Updated' : 'Created') . ": {$key}");
        }

        $this->info('Done installing teacher-late WhatsApp templates.');
        return self::SUCCESS;
    }
}
