<?php

namespace App\Console\Commands;

use App\Services\MonthlyDisciplineSummaryService;
use Illuminate\Console\Command;

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
     */
    public function handle(MonthlyDisciplineSummaryService $service)
    {
        $monthOption = $this->option('month');
        $dryRun = (bool) $this->option('dry-run');
        return $service->send($this->output, $monthOption, $dryRun);
    }

}
