<?php

namespace Tests\Unit\MonthlySummary;

use App\Services\MonthlySummary\TextMessageFormatter;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TextMessageFormatterTest extends TestCase
{
    private function fakeSelected(int $count): Collection
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $student = new \stdClass();
            $class = new \stdClass();
            $class->name = 'X IPA ' . (($i % 3) + 1);
            $student->name = 'Siswa ' . $i;
            $student->class = $class;

            $row = new \stdClass();
            $row->student = $student;
            $row->total_late = $i % 5;
            $row->total_absent = $i % 2;
            $row->score = -$i;
            $items[] = $row;
        }
        return collect($items);
    }

    #[Test]
    public function it_paginates_into_chunks_and_adds_header_footer_and_extra_count(): void
    {
        $formatter = new TextMessageFormatter();
        $selected = $this->fakeSelected(25); // will create 2 chunks: 20 + 5
        $thresholds = [
            'min_total_late' => 3,
            'min_total_absent' => 2,
            'min_score' => -5,
        ];
        $limit = 20;
        $extraCount = 5;

        $messages = $formatter->formatTextChunks($selected, 'Juli 2025', $thresholds, $limit, $extraCount);

        $this->assertCount(2, $messages);
        $this->assertStringContainsString('Ringkasan Disiplin Bulanan — Juli 2025', $messages[0]);
        $this->assertStringContainsString('(lanjutan)', $messages[1]);
        $this->assertStringContainsString('Kriteria: L>=3 atau A>=2 atau Skor<=-5. Batas: 20 siswa.', end($messages));
        $this->assertStringContainsString('(+5 siswa lainnya melebihi ambang', end($messages));
    }
}
