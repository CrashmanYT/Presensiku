<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use Carbon\Carbon;
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
     * Side effects:
     * - May perform HTTP requests (WhatsApp, optional PDF URL probe).
     * - Writes console output and application logs.
     * - Persists generated PDF to public storage when output is PDF.
     *
     * @param OutputInterface $output       Console output interface
     * @param string|null     $monthOption  Target month in YYYY-MM; if null, use previous month or scheduled time
     * @param bool            $dryRun       If true, no messages/documents are actually sent
     * @return int                            SymfonyCommand::SUCCESS or FAILURE
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

        // For automatic scheduler runs, only execute near the configured time on the 1st day of month
        if (!$monthOption) {
            $sendTimeString = SettingsHelper::get('notifications.whatsapp.monthly_summary.send_time', '07:30');
            $targetDateTime = now()->startOfMonth()->setTimeFromTimeString($sendTimeString);
            if (abs(now()->diffInMinutes($targetDateTime)) > 1) {
                return SymfonyCommand::SUCCESS; // Not yet time; exit silently
            }
        }

        try {
            $targetMonth = $monthOption
                ? Carbon::createFromFormat('Y-m', $monthOption)
                : now()->subMonth()->startOfMonth();
        } catch (\Exception) {
            $output->writeln('Format --month tidak valid. Gunakan format YYYY-MM, contoh: 2025-07');
            return SymfonyCommand::FAILURE;
        }

        $monthKey = $targetMonth->format('Y-m');
        $monthTitle = $targetMonth->locale(app()->getLocale() ?? 'id')->translatedFormat('F Y');

        $thresholds = $settings['thresholds'] ?? [];
        $limit = $settings['limit'] ?? 50;
        $outputFormat = $settings['output'] ?? 'text';

        $output->writeln("Mencari data peringkat disiplin untuk bulan: $monthKey ...");

        [$selected, $extraCount] = $this->candidateFinder->findCandidates($monthKey, $thresholds, (int) $limit);

        if ($selected->isEmpty()) {
            $message = "Ringkasan Disiplin Bulanan — $monthTitle\n\nTidak ada siswa yang memenuhi kriteria untuk ditindaklanjuti.";
            if ($dryRun) {
                $output->writeln("[DRY-RUN] Pesan yang akan dikirim ke $receiver:\n\n$message");
                return SymfonyCommand::SUCCESS;
            }

            $this->whatsappService->sendMessage($receiver, $message);
            $output->writeln('Pesan kosong (no data) telah dikirim ke kesiswaan.');
            return SymfonyCommand::SUCCESS;
        }

        if ($outputFormat === 'pdf_link' || $outputFormat === 'pdf_attachment') {
            $handled = $this->handlePdfOutput($output, $receiver, $dryRun, $selected, $monthKey, $monthTitle, $thresholds, (int) $limit, $extraCount, $outputFormat);
            if ($handled !== null) {
                return $handled;
            }
            // fallback ke teks jika null
        }

        // Fallback/format teks
        $chunks = $this->textFormatter->formatTextChunks($selected, $monthTitle, $thresholds, (int) $limit, $extraCount);
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

        $output->writeln(sprintf(
            'Ringkasan bulanan untuk %s %s dikirim ke %s (%d pesan).',
            $monthTitle,
            $dryRun ? '(DRY-RUN)' : '',
            $receiver,
            count($chunks)
        ));

        return SymfonyCommand::SUCCESS;
    }

    // Kandidat dipindahkan ke CandidateFinder

    // Formatting dipindahkan ke TextMessageFormatter

    /**
     * Handle PDF outputs according to requested format (link or attachment).
     * Falls back to text when PDF is not enabled or not reachable.
     *
     * Side effects:
     * - Stores PDF on public disk.
     * - Sends WhatsApp message or document.
     * - Performs an HTTP HEAD probe to the public URL for reachability.
     *
     * @param OutputInterface   $output
     * @param string            $receiver
     * @param bool              $dryRun
     * @param Collection        $selected
     * @param string            $monthKey
     * @param string            $monthTitle
     * @param array<string,mixed> $thresholds
     * @param int               $limit
     * @param int               $extraCount
     * @param string            $outputFormat    'pdf_link' or 'pdf_attachment'
     * @return int|null                          SUCCESS when handled; null to fall back to text
     */
    private function handlePdfOutput(
        OutputInterface $output,
        string $receiver,
        bool $dryRun,
        Collection $selected,
        string $monthKey,
        string $monthTitle,
        array $thresholds,
        int $limit,
        int $extraCount,
        string $outputFormat
    ): ?int {
        if (!$this->pdfReportService->isEnabled()) {
            $output->writeln('Output PDF diminta, tetapi Dompdf belum terpasang. Menggunakan format teks sebagai fallback.');
            return null; // fallback ke teks
        }

        $rows = $this->pdfReportService->buildRows($selected);
        $html = $this->pdfReportService->renderHtml($monthTitle, $rows, $thresholds, $limit, $extraCount);
        $pdfOutput = $this->pdfReportService->renderPdf($html);

        if ($outputFormat === 'pdf_link') {
            [$fileName, $publicUrl] = $this->pdfReportService->store($pdfOutput, $monthKey);
            $message = "Ringkasan Disiplin Bulanan — {$monthTitle}\n\nUnduh PDF: {$publicUrl}";

            if ($dryRun) {
                $output->writeln("[DRY-RUN] Akan mengirim tautan PDF ke {$receiver}: {$publicUrl}");
                return SymfonyCommand::SUCCESS;
            }

            $this->whatsappService->sendMessage($receiver, $message);
            $output->writeln('Tautan PDF ringkasan bulanan telah dikirim ke kesiswaan.');
            return SymfonyCommand::SUCCESS;
        }

        if ($outputFormat === 'pdf_attachment') {
            [$fileName, $publicUrl] = $this->pdfReportService->store($pdfOutput, $monthKey);

            if ($dryRun) {
                $output->writeln("[DRY-RUN] Akan mengirim lampiran PDF ke {$receiver}: {$publicUrl}");
                return SymfonyCommand::SUCCESS;
            }

            // Preflight: ensure the public URL is reachable (avoid Kirimi failing with 404/Page Not Found)
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

        return null;
    }
}
