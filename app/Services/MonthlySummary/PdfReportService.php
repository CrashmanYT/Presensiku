<?php

namespace App\Services\MonthlySummary;

use Dompdf\Dompdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for generating the Monthly Discipline Summary PDF.
 *
 * Responsibilities:
 * - Transform Eloquent models into table rows for the report.
 * - Render an HTML view that represents the report.
 * - Render HTML into a PDF using Dompdf.
 * - Store the PDF in the public disk and return an accessible URL.
 */
class PdfReportService
{
    /**
     * Determine if PDF generation is enabled (Dompdf available).
     * During unit tests, this returns false to keep tests deterministic.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        // During tests, treat PDF as disabled to keep tests deterministic
        if (function_exists('app') && app()->runningUnitTests()) {
            return false;
        }
        return class_exists(Dompdf::class);
    }

    /**
     * Convert selected rankings into a normalized array for the PDF table.
     *
     * @param Collection $selected Collection of ranking models with student and class relations
     * @return array<int, array{no:int,name:string,class:string,late:int,absent:int,score:int}>
     */
    public function buildRows(Collection $selected): array
    {
        return $selected->values()->map(function ($r, $i) {
            return [
                'no' => $i + 1,
                'name' => $r->student?->name ?? 'Tanpa Nama',
                'class' => $r->student?->class?->name ?? '-',
                'late' => (int) $r->total_late,
                'absent' => (int) $r->total_absent,
                'score' => (int) $r->score,
            ];
        })->all();
    }

    /**
     * Render the HTML view for the monthly report.
     *
     * @param string $monthTitle
     * @param array<int, array{no:int,name:string,class:string,late:int,absent:int,score:int}> $rows
     * @param array<string,mixed> $thresholds
     * @param int $limit
     * @param int $extraCount
     * @return string HTML output
     */
    public function renderHtml(string $monthTitle, array $rows, array $thresholds, int $limit, int $extraCount): string
    {
        return View::make('reports.monthly_discipline_summary', [
            'monthTitle' => $monthTitle,
            'rows' => $rows,
            'thresholds' => $thresholds,
            'limit' => $limit,
            'extraCount' => $extraCount,
        ])->render();
    }

    /**
     * Render a PDF binary string from the given HTML.
     *
     * @param string $html UTF-8 HTML string
     * @return string Binary PDF contents
     */
    public function renderPdf(string $html): string
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    /**
     * @return array{0:string,1:string,2:string} [fileName, publicUrl, relativePath]
     */
    public function store(string $pdfOutput, string $monthKey): array
    {
        $dir = 'wa_reports';
        $disk = Storage::disk('public');
        $disk->makeDirectory($dir);
        $fileName = sprintf('ringkasan-disiplin-%s.pdf', $monthKey);
        $relativePath = $dir . '/' . $fileName;
        $disk->put($relativePath, $pdfOutput);

        // Warn if the public storage symlink is missing, which will make the URL inaccessible
        $publicStoragePath = public_path('storage');
        if (!is_dir($publicStoragePath)) {
            Log::warning('Public storage symlink is missing. PDF URL may not be accessible. Run: php artisan storage:link');
        }

        $publicUrl = $disk->url($relativePath);
        return [$fileName, $publicUrl, $relativePath];
    }
}
