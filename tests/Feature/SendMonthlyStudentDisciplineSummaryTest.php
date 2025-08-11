<?php

namespace Tests\Feature;

use App\Console\Commands\SendMonthlyStudentDisciplineSummary;
use App\Helpers\SettingsHelper;
use App\Models\Classes;
use App\Models\DisciplineRanking;
use App\Models\Student;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendMonthlyStudentDisciplineSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function seedCommonSettings(): void
    {
        SettingsHelper::set('notifications.enabled', true, 'boolean');
        SettingsHelper::set('notifications.whatsapp.monthly_summary.enabled', true, 'boolean');
        SettingsHelper::set('notifications.whatsapp.monthly_summary.output', 'text', 'string');
        SettingsHelper::set('notifications.whatsapp.monthly_summary.thresholds.min_total_late', 3, 'integer');
        SettingsHelper::set('notifications.whatsapp.monthly_summary.thresholds.min_total_absent', 2, 'integer');
        SettingsHelper::set('notifications.whatsapp.monthly_summary.thresholds.min_score', -5, 'integer');
        SettingsHelper::set('notifications.whatsapp.monthly_summary.limit', 50, 'integer');
        SettingsHelper::set('notifications.whatsapp.monthly_summary.send_time', '07:30', 'string');
        SettingsHelper::set('notifications.whatsapp.student_affairs_number', '628111111111', 'string');
    }

    public function test_sends_summary_for_previous_month_with_filters(): void
    {
        $this->seedCommonSettings();

        $class = Classes::factory()->create(['name' => 'X IPA 1']);
        $studentHit = Student::factory()->create(['class_id' => $class->id, 'name' => 'Andi']);
        $studentMiss = Student::factory()->create(['class_id' => $class->id, 'name' => 'Budi']);

        $lastMonth = now()->subMonth()->startOfMonth();
        $monthKey = $lastMonth->format('Y-m');

        // Meets one of the OR thresholds (e.g., score <= -5)
        DisciplineRanking::create([
            'student_id' => $studentHit->id,
            'month' => $monthKey,
            'total_present' => 10,
            'total_absent' => 1,
            'total_late' => 1,
            'score' => -6,
        ]);

        // Does not meet any threshold
        DisciplineRanking::create([
            'student_id' => $studentMiss->id,
            'month' => $monthKey,
            'total_present' => 20,
            'total_absent' => 0,
            'total_late' => 0,
            'score' => 4,
        ]);

        $this->mock(WhatsappService::class, function ($mock) use ($studentHit) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function ($to, $message) use ($studentHit) {
                    return $to === '628111111111'
                        && str_contains($message, 'Ringkasan Disiplin Bulanan')
                        && str_contains($message, $studentHit->name)
                        && str_contains($message, 'L:1 A:1')
                        && str_contains($message, 'Skor:-6');
                });
        });

        $this->artisan('discipline:send-monthly-summary --month=' . $monthKey)
            ->assertExitCode(0);
    }

    public function test_pdf_link_output_falls_back_to_text_when_dompdf_missing(): void
    {
        $this->seedCommonSettings();
        SettingsHelper::set('notifications.whatsapp.monthly_summary.output', 'pdf_link', 'string');

        $class = Classes::factory()->create(['name' => 'X IPA 1']);
        $studentHit = Student::factory()->create(['class_id' => $class->id, 'name' => 'Budi']);
        $lastMonth = now()->subMonth()->startOfMonth();
        $monthKey = $lastMonth->format('Y-m');

        DisciplineRanking::create([
            'student_id' => $studentHit->id,
            'month' => $monthKey,
            'total_present' => 10,
            'total_absent' => 3,
            'total_late' => 4,
            'score' => -7,
        ]);

        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function ($to, $message) {
                    return $to === '628111111111' && str_contains($message, 'Ringkasan Disiplin Bulanan');
                });
        });

        $this->artisan('discipline:send-monthly-summary --month=' . $monthKey)
            ->assertExitCode(0);
    }

    public function test_pdf_attachment_output_falls_back_to_text_when_dompdf_missing(): void
    {
        $this->seedCommonSettings();
        SettingsHelper::set('notifications.whatsapp.monthly_summary.output', 'pdf_attachment', 'string');

        $class = Classes::factory()->create(['name' => 'X IPA 1']);
        $studentHit = Student::factory()->create(['class_id' => $class->id, 'name' => 'Andi']);
        $lastMonth = now()->subMonth()->startOfMonth();
        $monthKey = $lastMonth->format('Y-m');

        DisciplineRanking::create([
            'student_id' => $studentHit->id,
            'month' => $monthKey,
            'total_present' => 10,
            'total_absent' => 3,
            'total_late' => 1,
            'score' => -6,
        ]);

        // Since Dompdf is not installed by default, the command should continue with text sending path
        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function ($to, $message) {
                    return $to === '628111111111' && str_contains($message, 'Ringkasan Disiplin Bulanan');
                });
        });

        $this->artisan('discipline:send-monthly-summary --month=' . $monthKey)
            ->assertExitCode(0);
    }

    public function test_respects_limit_and_shows_extra_count_note(): void
    {
        $this->seedCommonSettings();
        SettingsHelper::set('notifications.whatsapp.monthly_summary.limit', 2, 'integer');

        $class = Classes::factory()->create(['name' => 'XI IPS 2']);
        $lastMonth = now()->subMonth()->startOfMonth();
        $monthKey = $lastMonth->format('Y-m');

        $students = Student::factory(3)->create(['class_id' => $class->id]);
        foreach ($students as $idx => $student) {
            DisciplineRanking::create([
                'student_id' => $student->id,
                'month' => $monthKey,
                'total_present' => 10,
                'total_absent' => 3, // meets threshold (>=2)
                'total_late' => 4,   // meets threshold (>=3)
                'score' => -7 - $idx,
            ]);
        }

        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function ($to, $message) {
                    return $to === '628111111111'
                        && str_contains($message, 'Ringkasan Disiplin Bulanan')
                        && str_contains($message, '(+1 siswa lainnya melebihi ambang');
                });
        });

        $this->artisan('discipline:send-monthly-summary --month=' . $monthKey)
            ->assertExitCode(0);
    }

    public function test_sends_no_data_message_when_no_matches(): void
    {
        $this->seedCommonSettings();

        $lastMonth = now()->subMonth()->startOfMonth();
        $monthKey = $lastMonth->format('Y-m');

        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function ($to, $message) {
                    return $to === '628111111111' && str_contains($message, 'Tidak ada siswa yang memenuhi kriteria');
                });
        });

        $this->artisan('discipline:send-monthly-summary --month=' . $monthKey)
            ->assertExitCode(0);
    }

    public function test_dry_run_does_not_send_any_message(): void
    {
        $this->seedCommonSettings();

        $class = Classes::factory()->create(['name' => 'XII IPA 3']);
        $student = Student::factory()->create(['class_id' => $class->id, 'name' => 'Siti']);
        $lastMonth = now()->subMonth()->startOfMonth();
        $monthKey = $lastMonth->format('Y-m');

        DisciplineRanking::create([
            'student_id' => $student->id,
            'month' => $monthKey,
            'total_present' => 10,
            'total_absent' => 3,
            'total_late' => 0,
            'score' => -1,
        ]);

        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')->never();
        });

        $this->artisan('discipline:send-monthly-summary --month=' . $monthKey . ' --dry-run')
            ->expectsOutputToContain('[DRY-RUN]')
            ->assertExitCode(0);
    }

    public function test_disabled_setting_skips_sending(): void
    {
        $this->seedCommonSettings();
        SettingsHelper::set('notifications.whatsapp.monthly_summary.enabled', false, 'boolean');

        $lastMonth = now()->subMonth()->startOfMonth();
        $monthKey = $lastMonth->format('Y-m');

        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')->never();
        });

        $this->artisan('discipline:send-monthly-summary --month=' . $monthKey)
            ->assertExitCode(0);
    }
}
