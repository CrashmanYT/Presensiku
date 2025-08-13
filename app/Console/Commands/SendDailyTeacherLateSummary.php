<?php

namespace App\Console\Commands;

use App\Services\TeacherLateDailyReportService;
use Illuminate\Console\Command;

class SendDailyTeacherLateSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:send-teacher-late-daily {--dry-run : Do not actually send WhatsApp messages} {--force : Bypass schedule time gate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a daily WhatsApp summary (PDF/text) of teachers who arrived late to the Administration number.';

    /**
     * Execute the console command.
     */
    public function handle(TeacherLateDailyReportService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        return $service->send($this->output, $dryRun, $force);
    }
}
