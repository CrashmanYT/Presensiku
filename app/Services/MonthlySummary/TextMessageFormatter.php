<?php

namespace App\Services\MonthlySummary;

use Illuminate\Support\Collection;

class TextMessageFormatter
{
    /**
     * Format selected rankings into paginated WhatsApp text messages.
     *
     * @return array<int, string>
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
