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

        // Define defaults as arrays of items (each item corresponds to a repeater row)
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

        foreach ($payloads as $base => $items) {
            // Detect existing data in either old JSON-at-base format or dot-indexed format.
            $exists = Setting::has($base) || Setting::has($base . '.0.message');

            if (! $overwrite && $exists) {
                $this->line("Skip (exists): {$base}");
                continue;
            }

            // If overwriting, clear previous values (both styles) for a clean slate.
            if ($overwrite) {
                // Remove old aggregated JSON row if present
                if (Setting::has($base)) {
                    Setting::forget($base);
                }
                // Remove dot-indexed children
                \App\Models\Setting::query()
                    ->where('key', 'like', $base . '.%')
                    ->delete();
            }

            // Write as dot-indexed repeater keys: {base}.{index}.message (and label if provided)
            foreach (array_values($items) as $i => $item) {
                $message = (string) ($item['message'] ?? '');
                if ($message === '') {
                    continue;
                }
                Setting::set($base . ".{$i}.message", $message, 'string', 'notifications');
                if (isset($item['label']) && $item['label'] !== '') {
                    Setting::set($base . ".{$i}.label", (string) $item['label'], 'string', 'notifications');
                }
            }

            $this->info(($overwrite ? 'Updated' : 'Created') . ": {$base}");
        }

        $this->info('Done installing Kesiswaan monthly WhatsApp templates.');
        return self::SUCCESS;
    }
}
