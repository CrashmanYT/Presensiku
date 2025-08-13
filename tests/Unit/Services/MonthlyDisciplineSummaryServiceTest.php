<?php

namespace Tests\Unit\Services;

use App\Services\MonthlyDisciplineSummaryService;
use App\Services\MonthlySummary\CandidateFinder;
use App\Services\MonthlySummary\PdfReportService;
use App\Services\MonthlySummary\TextMessageFormatter;
use App\Services\WhatsappService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonthlyDisciplineSummaryServiceTest extends TestCase
{
    private function makeService(): MonthlyDisciplineSummaryService
    {
        $wa = $this->createMock(WhatsappService::class);
        $finder = $this->createMock(CandidateFinder::class);
        $formatter = $this->createMock(TextMessageFormatter::class);
        $pdf = $this->createMock(PdfReportService::class);

        return new MonthlyDisciplineSummaryService($wa, $finder, $formatter, $pdf);
    }

    #[Test]
    public function should_send_now_returns_true_when_forced(): void
    {
        $svc = $this->makeService();
        $now = CarbonImmutable::parse('2025-08-13 10:00:00');
        $this->assertTrue($svc->shouldSendNow($now, '07:30', true));
    }

    #[Test]
    public function should_send_now_true_within_one_minute_window(): void
    {
        $svc = $this->makeService();
        // Target is startOfMonth at 07:30; simulate within 1 minute
        $now = CarbonImmutable::parse('2025-08-01 07:29:30');
        $this->assertTrue($svc->shouldSendNow($now, '07:30', false));
    }

    #[Test]
    public function should_send_now_false_outside_window(): void
    {
        $svc = $this->makeService();
        $now = CarbonImmutable::parse('2025-08-01 07:27:00');
        $this->assertFalse($svc->shouldSendNow($now, '07:30', false));
    }
}
