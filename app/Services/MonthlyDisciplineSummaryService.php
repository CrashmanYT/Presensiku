<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use App\Services\MonthlySummary\CandidateFinder;
use App\Services\MonthlySummary\PdfReportService;
use App\Services\MonthlySummary\TextMessageFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Orchestrates the monthly discipline summary workflow.
 *
 * Responsibilities:
 * - Read settings and determine target month.
 * - Query candidates via `CandidateFinder`.
 * - Format text chunks via `TextMessageFormatter`.
 * - Generate and store PDF via `PdfReportService` when requested.
 * - Send WhatsApp messages/documents via `WhatsappService`.
 */
class MonthlyDisciplineSummaryService
{
    /**
     * Inject dependencies for the summary workflow.
     */
    public function __construct(
        private WhatsappService $whatsappService,
        private CandidateFinder $candidateFinder,
        private TextMessageFormatter $textFormatter,
        private PdfReportService $pdfReportService,
    ) {
    }

    /**
     * Entry point to send the monthly discipline summary.
     *
     * Keeps orchestration thin by delegating to testable helpers.
     */
    public function send(OutputInterface $output, ?string $monthOption, bool $dryRun): int
    {
        $settings = SettingsHelper::getMonthlySummarySettings();
        if (!($settings['enabled'] ?? false)) {
            $output->writeln('Monthly summary is disabled via settings. Exiting.');
            return SymfonyCommand::SUCCESS;
        }

        $receiver = SettingsHelper::get('notifications.whatsapp.student_affairs_number', '');
        if (empty($receiver)) {
            $output->writeln('Nomor WhatsApp kesiswaan belum diatur (notifications.whatsapp.student_affairs_number).');
            Log::warning('Monthly summary not sent: student_affairs_number is empty.');
            return SymfonyCommand::SUCCESS;
        }

        $now = CarbonImmutable::now();
        $sendTimeString = SettingsHelper::get('notifications.whatsapp.monthly_summary.send_time', '07:30');
        $force = (bool) $monthOption; // manual run bypasses schedule
        if (!$this->shouldSendNow($now, $sendTimeString, $force)) {
            return SymfonyCommand::SUCCESS; // exit silently if not time yet
        }

        try {
            $targetMonth = $monthOption
                ? Carbon::createFromFormat('Y-m', $monthOption)
                : $now->subMonth()->startOfMonth();
        } catch (\Exception) {
            $output->writeln('Format --month tidak valid. Gunakan format YYYY-MM, contoh: 2025-07');
            return SymfonyCommand::FAILURE;
        }

        $monthKey = $targetMonth->format('Y-m');
        $monthTitle = $targetMonth->locale(app()->getLocale() ?? 'id')->translatedFormat('F Y');

        $thresholds = $settings['thresholds'] ?? [];
        $limit = (int) ($settings['limit'] ?? 50);
        $outputFormat = $settings['output'] ?? 'text';

        $output->writeln("Mencari data peringkat disiplin untuk bulan: {$monthKey} ...");

        [$selected, $extraCount] = $this->collectAttendances($monthKey, $thresholds, $limit);

        if ($selected->isEmpty()) {
            $message = "Ringkasan Disiplin Bulanan — {$monthTitle}\n\nTidak ada siswa yang memenuhi kriteria untuk ditindaklanjuti.";
            if ($dryRun) {
                $output->writeln("[DRY-RUN] Pesan yang akan dikirim ke {$receiver}:\n\n{$message}");
                return SymfonyCommand::SUCCESS;
            }

            $this->whatsappService->sendMessage($receiver, $message);
            $output->writeln('Pesan kosong (no data) telah dikirim ke kesiswaan.');
            return SymfonyCommand::SUCCESS;
        }

        if ($outputFormat === 'pdf_link' || $outputFormat === 'pdf_attachment') {
            $rows = $this->buildRows($selected);
            $pdfMeta = $this->renderAndStorePdf($rows, $monthKey, $monthTitle, $thresholds, $limit, $extraCount);
            if ($pdfMeta !== null) {
                if ($outputFormat === 'pdf_link') {
                    return $this->sendPdfLink($output, $receiver, $pdfMeta['publicUrl'], $monthTitle, $dryRun);
                }
                return $this->sendPdfAttachment($output, $receiver, $pdfMeta['publicUrl'], $pdfMeta['fileName'], $monthTitle, $dryRun);
            }
            // fallback to text if PDF is disabled/unavailable
        }

        $chunks = $this->textFormatter->formatTextChunks($selected, $monthTitle, $thresholds, $limit, $extraCount);
        $sentCount = $this->sendTextList($output, $receiver, $chunks, $dryRun);

        $output->writeln(sprintf(
            'Ringkasan bulanan untuk %s %s dikirim ke %s (%d pesan).',
            $monthTitle,
            $dryRun ? '(DRY-RUN)' : '',
            $receiver,
            $sentCount
        ));

        return SymfonyCommand::SUCCESS;
    }

    // Kandidat dipindahkan ke CandidateFinder

    // Formatting dipindahkan ke TextMessageFormatter

    /**
     * Decide whether the job should send now.
     */
    public function shouldSendNow(CarbonInterface $now, string $sendTime, bool $force): bool
    {
        if ($force) {
            return true;
        }
        $targetDateTime = $now->startOfMonth()->setTimeFromTimeString($sendTime);
        return abs($now->diffInMinutes($targetDateTime)) <= 1;
    }

    /**
     * Query attendance candidates for a given month key.
     *
     * @return array{0: Collection, 1: int} [$selected, $extraCount]
     */
    protected function collectAttendances(string $monthKey, array $thresholds, int $limit): array
    {
        return $this->candidateFinder->findCandidates($monthKey, $thresholds, $limit);
    }

    /**
     * Build PDF rows from selected candidates (pure transformation).
     *
     * @return array<int,array<string,mixed>>
     */
    protected function buildRows(Collection $selected): array
    {
        return $this->pdfReportService->buildRows($selected);
    }

    /**
     * Render HTML and PDF, then store into public disk.
     * Returns metadata for subsequent sending.
     *
     * @return array{publicUrl: string, fileName: string, diskPath: string}|null
     */
    protected function renderAndStorePdf(
        array $rows,
        string $monthKey,
        string $monthTitle,
        array $thresholds,
        int $limit,
        int $extraCount
    ): ?array {
        if (!$this->pdfReportService->isEnabled()) {
            return null;
        }
        $html = $this->pdfReportService->renderHtml($monthTitle, $rows, $thresholds, $limit, $extraCount);
        $pdfOutput = $this->pdfReportService->renderPdf($html);
        [$fileName, $publicUrl, $relativePath] = $this->pdfReportService->store($pdfOutput, $monthKey);
        return [
            'publicUrl' => $publicUrl,
            'fileName' => $fileName,
            'diskPath' => $relativePath,
        ];
    }

    /**
     * Send a simple PDF link via WhatsApp.
     */
    protected function sendPdfLink(OutputInterface $output, string $receiver, string $publicUrl, string $monthTitle, bool $dryRun): int
    {
        $message = "Ringkasan Disiplin Bulanan — {$monthTitle}\n\nUnduh PDF: {$publicUrl}";
        if ($dryRun) {
            $output->writeln("[DRY-RUN] Akan mengirim tautan PDF ke {$receiver}: {$publicUrl}");
            return SymfonyCommand::SUCCESS;
        }
        $this->whatsappService->sendMessage($receiver, $message);
        $output->writeln('Tautan PDF ringkasan bulanan telah dikirim ke kesiswaan.');
        return SymfonyCommand::SUCCESS;
    }

    /**
     * Send a PDF as a WhatsApp document, with preflight reachability probe.
     */
    protected function sendPdfAttachment(
        OutputInterface $output,
        string $receiver,
        string $publicUrl,
        string $fileName,
        string $monthTitle,
        bool $dryRun
    ): int {
        if ($dryRun) {
            $output->writeln("[DRY-RUN] Akan mengirim lampiran PDF ke {$receiver}: {$publicUrl}");
            return SymfonyCommand::SUCCESS;
        }

        try {
            $probe = Http::timeout(10)->head($publicUrl);
        } catch (\Throwable $e) {
            $probe = null;
        }
        if (!$probe || !$probe->successful()) {
            $status = $probe?->status() ?? 'n/a';
            $output->writeln("URL PDF tidak dapat diakses (status: {$status}). Mengirim tautan sebagai pesan teks.");
            $fallbackMessage = "Ringkasan Disiplin Bulanan — {$monthTitle}\n\nUnduh PDF: {$publicUrl}";
            $this->whatsappService->sendMessage($receiver, $fallbackMessage);
            $output->writeln('Tautan PDF telah dikirim sebagai pesan teks ke kesiswaan.');
            return SymfonyCommand::SUCCESS;
        }

        $result = $this->whatsappService->sendDocument($receiver, $publicUrl, "Ringkasan Disiplin Bulanan — {$monthTitle}", $fileName);
        if (($result['success'] ?? false) === true) {
            $output->writeln('Lampiran PDF ringkasan bulanan telah dikirim ke kesiswaan.');
            return SymfonyCommand::SUCCESS;
        }

        $output->writeln('Gagal mengirim lampiran PDF melalui WhatsApp. Mengirim tautan sebagai pesan teks. Error: ' . ($result['error'] ?? 'unknown'));
        $fallbackMessage = "Ringkasan Disiplin Bulanan — {$monthTitle}\n\nUnduh PDF: {$publicUrl}";
        $this->whatsappService->sendMessage($receiver, $fallbackMessage);
        $output->writeln('Tautan PDF telah dikirim sebagai pesan teks ke kesiswaan.');
        return SymfonyCommand::SUCCESS;
    }

    /**
     * Send a list of text chunks through WhatsApp.
     * Returns number of messages processed.
     */
    protected function sendTextList(OutputInterface $output, string $receiver, array $chunks, bool $dryRun): int
    {
        foreach ($chunks as $index => $message) {
            if ($dryRun) {
                $output->writeln(sprintf(
                    "[DRY-RUN] Pesan %d/%d yang akan dikirim ke %s:\n\n%s",
                    $index + 1,
                    count($chunks),
                    $receiver,
                    $message
                ));
            } else {
                $this->whatsappService->sendMessage($receiver, $message);
                usleep(300000); // 300ms to avoid rate-limits
            }
        }

        return count($chunks);
    }
}
