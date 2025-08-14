<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;

class InstallKesiswaanMonthlyTemplates extends Command
{
    protected $signature = 'settings:install-kesiswaan-monthly-templates {--overwrite : Overwrite existing values if present}';

    protected $description = 'Install default WhatsApp templates for monthly student discipline summary to Student Affairs (Kesiswaan).';

    public function handle(): int
    {
        $overwrite = (bool) $this->option('overwrite');

        $payloads = [
            'notifications.whatsapp.templates.monthly_summary_no_data' => [
                ['message' => "Ringkasan Disiplin Bulanan — {month_title}\n\nTidak ada siswa yang memenuhi kriteria untuk ditindaklanjuti."],
            ],
            'notifications.whatsapp.templates.monthly_summary_pdf_link' => [
                ['message' => "Ringkasan Disiplin Bulanan — {month_title}\n\nUnduh PDF: {pdf_url}"],
            ],
            'notifications.whatsapp.templates.monthly_summary_pdf_attachment_caption' => [
                ['message' => "Ringkasan Disiplin Bulanan — {month_title}"],
            ],
            'notifications.whatsapp.templates.monthly_summary_text' => [
                ['message' => "Ringkasan Disiplin Bulanan — {month_title}\n\n{list}"],
            ],
        ];

        foreach ($payloads as $key => $value) {
            if (! $overwrite && Setting::has($key)) {
                $this->line("Skip (exists): {$key}");
                continue;
            }
            Setting::set($key, $value, 'json', 'notifications');
            $this->info(($overwrite ? 'Updated' : 'Created') . ": {$key}");
        }

        $this->info('Done installing Kesiswaan monthly WhatsApp templates.');
        return self::SUCCESS;
    }
}
