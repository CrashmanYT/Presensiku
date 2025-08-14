<?php

namespace App\Services;

use App\Enums\AttendanceStatusEnum;
use App\Contracts\SettingsRepositoryInterface;
use App\Models\TeacherAttendance;
use App\Services\MonthlySummary\PdfReportService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Output\OutputInterface;
use App\Support\TimeGate;
use App\Services\MessageTemplateService;

class TeacherLateDailyReportService
{
    public function __construct(
        private WhatsappService $whatsappService,
        private AttendanceService $attendanceService,
        private PdfReportService $pdfReportService,
        private SettingsRepositoryInterface $settings,
        private MessageTemplateService $messageTemplateService,
    ) {
    }

    /**
     * Generate and send the daily teacher-late report to Administration via WhatsApp.
     *
     * - Time-gated using settings send_time (within +/- 1 minute)
     * - Output as PDF link/attachment when configured and Dompdf is available
     * - Falls back to text list when no PDF or on failures
     */
    public function send(OutputInterface $output, bool $dryRun, bool $force = false): int
    {
        $now = CarbonImmutable::now();
        $enabled = (bool) $this->settings->get('notifications.whatsapp.teacher_late_daily.enabled', true);
        if (!$enabled) {
            $output->writeln('Daily teacher-late summary is disabled via settings. Exiting.');
            return SymfonyCommand::SUCCESS;
        }

        $receiver = (string) $this->settings->get('notifications.whatsapp.administration_number', '');
        if (empty($receiver)) {
            if ($dryRun) {
                $receiver = '(DRY-RUN: administration_number not set)';
            } else {
                $output->writeln('Nomor WhatsApp Tata Usaha belum diatur (notifications.whatsapp.administration_number).');
                Log::warning('TeacherLateDailyReport not sent: administration_number is empty.');
                return SymfonyCommand::SUCCESS;
            }
        }

        // Time gate: only run near the configured time unless forced
        $sendTimeString = (string) $this->settings->get('notifications.whatsapp.teacher_late_daily.send_time', '08:00');
        $timeGate = app(TimeGate::class);
        if (! $timeGate->isWithinWindow($now, $now->setTimeFromTimeString($sendTimeString), $force, 1)) {
            return SymfonyCommand::SUCCESS; // Not yet time; exit silently
        }

        $today = $now->toDateString();
        $dateTitle = $now->locale(app()->getLocale() ?? 'id')->translatedFormat('d F Y');

        $attendances = TeacherAttendance::whereDate('date', $today)
            ->where('status', AttendanceStatusEnum::TERLAMBAT)
            ->with('teacher')
            ->orderBy('time_in')
            ->get();

        if ($attendances->isEmpty()) {
            // Template: report_teacher_late_no_data
            $message = $this->messageTemplateService->renderByType('report_teacher_late_no_data', [
                'date_title' => $dateTitle,
            ]);
            if ($message === '') {
                $message = "Laporan Guru Terlambat — {$dateTitle}\n\nTidak ada guru yang terlambat hari ini."; // fallback
            }
            if ($dryRun) {
                $output->writeln("[DRY-RUN] Pesan yang akan dikirim ke {$receiver}:\n\n{$message}");
                return SymfonyCommand::SUCCESS;
            }
            $this->whatsappService->sendMessage($receiver, $message);
            $output->writeln('Pesan kosong (no data) telah dikirim ke Tata Usaha.');
            return SymfonyCommand::SUCCESS;
        }

        // Determine reference end time for check-in window
        $rule = $this->attendanceService->getAttendanceRule(null, now());
        $timeInEnd = Carbon::parse($rule->time_in_end)->format('H:i:s');

        $rows = $this->buildRows($attendances, $today, $timeInEnd);
        $outputFormat = (string) $this->settings->get('notifications.whatsapp.teacher_late_daily.output', 'pdf_link');

        if (in_array($outputFormat, ['pdf_link', 'pdf_attachment'], true)) {
            if (!$this->pdfReportService->isEnabled()) {
                $output->writeln('Output PDF diminta, tetapi Dompdf belum terpasang. Menggunakan format teks sebagai fallback.');
            } else {
                $html = View::make('reports.teacher_late_daily', [
                    'dateTitle' => $dateTitle,
                    'rows' => $rows,
                ])->render();
                $pdfOutput = $this->pdfReportService->renderPdf($html);

                // Store to public disk
                $dir = 'wa_reports';
                $disk = Storage::disk('public');
                $disk->makeDirectory($dir);
                $dateKey = now()->format('Y-m-d');
                $fileName = sprintf('guru-terlambat-%s.pdf', $dateKey);
                $relativePath = $dir . '/' . $fileName;
                $disk->put($relativePath, $pdfOutput);

                // Warn if storage symlink missing
                if (!is_dir(public_path('storage'))) {
                    Log::warning('Public storage symlink is missing. PDF URL may not be accessible. Run: php artisan storage:link');
                }

                $publicUrl = Storage::url($relativePath);
                $absoluteUrl = url($publicUrl);

                if ($outputFormat === 'pdf_link') {
                    // Template: report_teacher_late_pdf_link
                    $message = $this->messageTemplateService->renderByType('report_teacher_late_pdf_link', [
                        'date_title' => $dateTitle,
                        'pdf_url' => $absoluteUrl,
                    ]);
                    if ($message === '') {
                        $message = "Laporan Guru Terlambat — {$dateTitle}\n\nUnduh PDF: {$absoluteUrl}"; // fallback
                    }
                    if ($dryRun) {
                        $output->writeln("[DRY-RUN] Akan mengirim tautan PDF ke {$receiver}: {$absoluteUrl}");
                        return SymfonyCommand::SUCCESS;
                    }
                    $result = $this->whatsappService->sendMessage($receiver, $message);
                    if (($result['success'] ?? false) === true) {
                        $output->writeln('Tautan PDF laporan harian telah dikirim ke Tata Usaha.');
                    } else {
                        $output->writeln('Gagal mengirim tautan PDF melalui WhatsApp. Error: ' . ($result['error'] ?? 'unknown'));
                    }
                    return SymfonyCommand::SUCCESS;
                }

                // pdf_attachment
                if ($dryRun) {
                    $output->writeln("[DRY-RUN] Akan mengirim lampiran PDF ke {$receiver}: {$absoluteUrl}");
                    return SymfonyCommand::SUCCESS;
                }

                // Preflight reachability (best-effort). Even if it fails, still attempt sendDocument.
                try {
                    $probe = Http::timeout(10)->head($absoluteUrl);
                    if (!$probe->successful()) {
                        $output->writeln("Peringatan: HEAD ke URL PDF gagal (status: {$probe->status()}). Tetap mencoba kirim lampiran...");
                    }
                } catch (\Throwable $e) {
                    $output->writeln('Peringatan: HEAD ke URL PDF melempar exception. Tetap mencoba kirim lampiran...');
                }

                // Template caption: report_teacher_late_pdf_attachment_caption
                $caption = $this->messageTemplateService->renderByType('report_teacher_late_pdf_attachment_caption', [
                    'date_title' => $dateTitle,
                ]);
                if ($caption === '') {
                    $caption = "Laporan Guru Terlambat — {$dateTitle}"; // fallback
                }
                $result = $this->whatsappService->sendDocument($receiver, $absoluteUrl, $caption, $fileName);
                if (($result['success'] ?? false) === true) {
                    $output->writeln('Lampiran PDF laporan harian telah dikirim ke Tata Usaha.');
                    return SymfonyCommand::SUCCESS;
                }

                $output->writeln('Gagal mengirim lampiran PDF melalui WhatsApp. Mengirim tautan sebagai pesan teks. Error: ' . ($result['error'] ?? 'unknown'));
                // Fallback as text link (same as pdf_link template)
                $fallbackMessage = $this->messageTemplateService->renderByType('report_teacher_late_pdf_link', [
                    'date_title' => $dateTitle,
                    'pdf_url' => $absoluteUrl,
                ]);
                if ($fallbackMessage === '') {
                    $fallbackMessage = "Laporan Guru Terlambat — {$dateTitle}\n\nUnduh PDF: {$absoluteUrl}"; // fallback
                }
                $fallback = $this->whatsappService->sendMessage($receiver, $fallbackMessage);
                if (($fallback['success'] ?? false) === true) {
                    $output->writeln('Tautan PDF sebagai fallback telah dikirim.');
                } else {
                    $output->writeln('Gagal mengirim fallback tautan PDF. Error: ' . ($fallback['error'] ?? 'unknown'));
                }
                return SymfonyCommand::SUCCESS;
            }
        }

        // Fallback to text list
        $list = collect($rows)
            ->map(fn ($r) => sprintf('- %s (NIP: %s) jam %s (%d menit)', $r['name'], $r['nip'], $r['time_in'], $r['minutes_late']))
            ->implode("\n");

        // Template: report_teacher_late_text
        $message = $this->messageTemplateService->renderByType('report_teacher_late_text', [
            'date_title' => $dateTitle,
            'list' => $list,
        ]);
        if ($message === '') {
            $message = "Laporan Guru Terlambat — {$dateTitle}\n\n{$list}"; // fallback
        }
        if ($dryRun) {
            $output->writeln("[DRY-RUN] Pesan teks ke {$receiver}:\n\n{$message}");
            return SymfonyCommand::SUCCESS;
        }
        $res = $this->whatsappService->sendMessage($receiver, $message);
        if (($res['success'] ?? false) === true) {
            $output->writeln('Pesan teks laporan harian telah dikirim ke Tata Usaha.');
        } else {
            $output->writeln('Gagal mengirim pesan teks laporan harian. Error: ' . ($res['error'] ?? 'unknown'));
        }
        return SymfonyCommand::SUCCESS;
    }

    /**
     * @param Collection<int,TeacherAttendance> $attendances
     * @return array<int, array{no:int,name:string,nip:string,time_in:string,minutes_late:int}>
     */
    private function buildRows(Collection $attendances, string $today, string $timeInEnd): array
    {
        return $attendances->values()->map(function ($att, $i) use ($today, $timeInEnd) {
            $ti = $att->time_in instanceof Carbon ? $att->time_in : Carbon::parse($att->time_in);
            $base = Carbon::parse($today . ' ' . $timeInEnd);
            $diff = $base->diffInMinutes($ti, false);
            $minutesLate = max(0, (int) $diff);

            return [
                'no' => $i + 1,
                'name' => $att->teacher?->name ?? 'Tanpa Nama',
                'nip' => $att->teacher?->nip ?? '-',
                'time_in' => $ti->format('H:i'),
                'minutes_late' => $minutesLate,
            ];
        })->all();
    }
}
