<?php

namespace App\Console\Commands;

use App\Services\MonthlyDisciplineSummaryService;
use Illuminate\Console\Command;

/**
 * Artisan command to send the Monthly Student Discipline Summary via WhatsApp.
 *
 * Options:
 * - --month=YYYY-MM  Send report for a specific month; if omitted, uses previous month.
 * - --dry-run        Do not actually send messages; only print to console.
 */
class SendMonthlyStudentDisciplineSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discipline:send-monthly-summary {--month=} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim ringkasan bulanan siswa dengan catatan disiplin buruk ke WhatsApp Kesiswaan.';

    /**
     * Execute the console command.
     *
     * Side effects:
     * - Delegates to `MonthlyDisciplineSummaryService::send()` which may send
     *   WhatsApp messages and generate/store a PDF, unless --dry-run is used.
     *
     * @param MonthlyDisciplineSummaryService $service
     * @return int
     */
    public function handle(MonthlyDisciplineSummaryService $service)
    {
        $monthOption = $this->option('month');
        $dryRun = (bool) $this->option('dry-run');
        return $service->send($this->output, $monthOption, $dryRun);
    }

}
