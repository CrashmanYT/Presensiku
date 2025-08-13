<?php

namespace Tests\Unit\MonthlySummary;

use App\Services\MonthlySummary\PdfReportService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PdfReportServiceTest extends TestCase
{
    private function fakeSelected(int $count): Collection
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $student = new \stdClass();
            $class = new \stdClass();
            $class->name = 'XI IPS ' . (($i % 3) + 1);
            $student->name = 'Siswa ' . $i;
            $student->class = $class;

            $row = new \stdClass();
            $row->student = $student;
            $row->total_late = $i % 4;
            $row->total_absent = $i % 3;
            $row->score = -$i;
            $items[] = $row;
        }
        return collect($items);
    }

    #[Test]
    public function it_builds_rows_from_selected_models(): void
    {
        $service = new PdfReportService();
        $selected = $this->fakeSelected(3);
        $rows = $service->buildRows($selected);

        $this->assertCount(3, $rows);
        $this->assertSame(1, $rows[0]['no']);
        $this->assertArrayHasKey('name', $rows[0]);
        $this->assertArrayHasKey('class', $rows[0]);
        $this->assertArrayHasKey('late', $rows[0]);
        $this->assertArrayHasKey('absent', $rows[0]);
        $this->assertArrayHasKey('score', $rows[0]);
    }

    #[Test]
    public function it_renders_html_with_title_rows_and_footer(): void
    {
        $service = new PdfReportService();
        $rows = [
            ['no' => 1, 'name' => 'Siswa 1', 'class' => 'X IPA 1', 'late' => 2, 'absent' => 1, 'score' => -3],
        ];
        $thresholds = ['min_total_late' => 3, 'min_total_absent' => 2, 'min_score' => -5];
        $limit = 50;
        $extraCount = 1;

        $html = $service->renderHtml('Juli 2025', $rows, $thresholds, $limit, $extraCount);

        $this->assertStringContainsString('Ringkasan Disiplin Bulanan — Juli 2025', $html);
        $this->assertStringContainsString('Siswa 1', $html);
        $this->assertStringContainsString('(+1 siswa lainnya melebihi ambang', $html);
    }
}
