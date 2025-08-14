<?php

namespace App\Services\MonthlySummary;

use Illuminate\Support\Collection;

/**
 * Formats selected discipline rankings into paginated WhatsApp text messages
 * including a header, a body list of students, and a footer with criteria.
 */
class TextMessageFormatter
{
    /**
     * Build the raw list lines and a footer string used by templated messages.
     *
     * @param Collection          $selected
     * @param array<string,mixed> $thresholds
     * @param int                 $limit
     * @param int                 $extraCount
     * @return array{0: array<int,string>, 1: string}
     */
    public function buildLinesAndFooter(Collection $selected, array $thresholds, int $limit, int $extraCount): array
    {
        $lines = $selected->map(function ($r) {
            $studentName = $r->student?->name ?? 'Tanpa Nama';
            $className = $r->student?->class?->name ?? '-';
            return sprintf('- %s (%s) | L:%d A:%d | Skor:%d',
                $studentName,
                $className,
                (int) $r->total_late,
                (int) $r->total_absent,
                (int) $r->score
            );
        })->all();

        if ($extraCount > 0) {
            $lines[] = sprintf('(+%d siswa lainnya melebihi ambang, tidak ditampilkan karena melewati batas ringkasan)', $extraCount);
        }

        if (isset($thresholds['min_total_late'], $thresholds['min_total_absent'], $thresholds['min_score'])) {
            $footer = "Kriteria: L>=%d atau A>=%d atau Skor<=%d. Batas: %d siswa.";
            $footer = sprintf($footer,
                (int) $thresholds['min_total_late'],
                (int) $thresholds['min_total_absent'],
                (int) $thresholds['min_score'],
                (int) $limit
            );
        } else {
            $footer = 'Kriteria: berdasarkan konfigurasi. Batas: ' . (int) $limit . ' siswa.';
        }

        return [$lines, $footer];
    }

    /**
     * Format selected rankings into paginated WhatsApp text messages.
     *
     * The message list is split into chunks of 20 lines to avoid overly long
     * messages. The first chunk includes the header; the last chunk includes
     * the footer describing thresholds and limits.
     *
     * @param Collection          $selected    Selected discipline ranking models
     * @param string              $monthTitle  Month title used in header
     * @param array<string,mixed> $thresholds  Thresholds config for footer
     * @param int                 $limit       Display limit used for footer
     * @param int                 $extraCount  Number of hidden rows beyond limit
     * @return array<int, string>              Message chunks ready for WhatsApp
     */
    public function formatTextChunks(Collection $selected, string $monthTitle, array $thresholds, int $limit, int $extraCount): array
    {
        $lines = $selected->map(function ($r) {
            $studentName = $r->student?->name ?? 'Tanpa Nama';
            $className = $r->student?->class?->name ?? '-';
            return sprintf('- %s (%s) | L:%d A:%d | Skor:%d',
                $studentName,
                $className,
                (int) $r->total_late,
                (int) $r->total_absent,
                (int) $r->score
            );
        })->all();

        if ($extraCount > 0) {
            $lines[] = sprintf('(+%d siswa lainnya melebihi ambang, tidak ditampilkan karena melewati batas ringkasan)', $extraCount);
        }

        $header = "Ringkasan Disiplin Bulanan — $monthTitle";
        if (isset($thresholds['min_total_late'], $thresholds['min_total_absent'], $thresholds['min_score'])) {
            $footer = "\n\nKriteria: L>=%d atau A>=%d atau Skor<=%d. Batas: %d siswa.";
            $footer = sprintf($footer,
                (int) $thresholds['min_total_late'],
                (int) $thresholds['min_total_absent'],
                (int) $thresholds['min_score'],
                (int) $limit
            );
        } else {
            $footer = "\n\nKriteria: berdasarkan konfigurasi. Batas: " . (int) $limit . " siswa.";
        }

        $chunks = array_chunk($lines, 20);
        $messages = [];
        foreach ($chunks as $index => $chunk) {
            $body = implode("\n", $chunk);
            $prefix = $index === 0 ? $header . "\n\n" : "(lanjutan)\n\n";
            $suffix = ($index === count($chunks) - 1) ? $footer : '';
            $messages[] = $prefix . $body . $suffix;
        }

        return $messages;
    }
}
