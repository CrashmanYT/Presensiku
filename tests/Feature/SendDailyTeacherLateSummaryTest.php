<?php

namespace Tests\Feature;

use App\Console\Commands\SendDailyTeacherLateSummary;
use App\Enums\AttendanceStatusEnum;
use App\Helpers\SettingsHelper;
use App\Models\AttendanceRule;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Services\AttendanceService;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendDailyTeacherLateSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function seedCommonSettings(bool $enabled = true, string $sendTime = '08:00', string $output = 'text'): void
    {
        SettingsHelper::set('notifications.whatsapp.teacher_late_daily.enabled', $enabled, 'boolean');
        SettingsHelper::set('notifications.whatsapp.teacher_late_daily.send_time', $sendTime, 'string');
        SettingsHelper::set('notifications.whatsapp.teacher_late_daily.output', $output, 'string');
        SettingsHelper::set('notifications.whatsapp.administration_number', '628222222222', 'string');
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Freeze time for deterministic tests
        $now = CarbonImmutable::create(2025, 8, 13, 8, 0, 0);
        CarbonImmutable::setTestNow($now);
        Carbon::setTestNow(Carbon::instance($now));
    }

    public function test_skips_when_disabled(): void
    {
        $this->seedCommonSettings(enabled: false);

        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')->never();
        });

        $this->artisan('attendance:send-teacher-late-daily')
            ->assertExitCode(0);
    }

    public function test_skips_when_not_in_send_time_window(): void
    {
        // Now is 08:00, set send_time far away
        $this->seedCommonSettings(enabled: true, sendTime: '22:00', output: 'text');

        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')->never();
        });

        $this->artisan('attendance:send-teacher-late-daily')
            ->assertExitCode(0);
    }

    public function test_sends_no_data_message_when_no_teachers_late(): void
    {
        $this->seedCommonSettings(enabled: true, sendTime: '08:00', output: 'text');

        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function ($to, $message) {
                    return $to === '628222222222' && str_contains($message, 'Tidak ada guru yang terlambat hari ini.');
                })
                ->andReturn(['success' => true]);
        });

        $this->artisan('attendance:send-teacher-late-daily')
            ->assertExitCode(0);
    }

    public function test_sends_text_list_when_output_text_and_in_time_window(): void
    {
        $this->seedCommonSettings(enabled: true, sendTime: '08:00', output: 'text');

        // Stub attendance rule end time to 07:30 for minutes late calculation
        $rule = AttendanceRule::factory()->make(['time_in_end' => '07:30:00']);
        $this->mock(AttendanceService::class, function ($mock) use ($rule) {
            $mock->shouldReceive('getAttendanceRule')
                ->andReturn($rule);
        });

        // Create two late teachers today
        $t1 = Teacher::factory()->create(['name' => 'Ani', 'nip' => '123']);
        $t2 = Teacher::factory()->create(['name' => 'Budi', 'nip' => '456']);

        TeacherAttendance::create([
            'teacher_id' => $t1->id,
            'date' => Carbon::now()->toDateString(),
            'time_in' => '07:45:00', // 15 minutes late from 07:30
            'time_out' => '15:00:00',
            'status' => AttendanceStatusEnum::TERLAMBAT,
            'device_id' => \App\Models\Device::factory()->create()->id,
        ]);
        TeacherAttendance::create([
            'teacher_id' => $t2->id,
            'date' => Carbon::now()->toDateString(),
            'time_in' => '07:40:00', // 10 minutes late
            'time_out' => '15:00:00',
            'status' => AttendanceStatusEnum::TERLAMBAT,
            'device_id' => \App\Models\Device::factory()->create()->id,
        ]);

        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function ($to, $message) {
                    return $to === '628222222222'
                        && str_contains($message, 'Laporan Guru Terlambat')
                        && str_contains($message, 'Ani') && str_contains($message, 'jam 07:45 (15 menit)')
                        && str_contains($message, 'Budi') && str_contains($message, 'jam 07:40 (10 menit)');
                })
                ->andReturn(['success' => true]);
        });

        $this->artisan('attendance:send-teacher-late-daily')
            ->assertExitCode(0);
    }

    public function test_force_bypasses_time_gate_and_sends(): void
    {
        // Now is 08:00, set send_time far away but use --force
        $this->seedCommonSettings(enabled: true, sendTime: '22:00', output: 'text');

        $rule = AttendanceRule::factory()->make(['time_in_end' => '07:30:00']);
        $this->mock(AttendanceService::class, function ($mock) use ($rule) {
            $mock->shouldReceive('getAttendanceRule')->andReturn($rule);
        });

        $t = Teacher::factory()->create(['name' => 'Cici', 'nip' => '789']);
        TeacherAttendance::create([
            'teacher_id' => $t->id,
            'date' => Carbon::now()->toDateString(),
            'time_in' => '07:50:00', // 20 minutes late
            'time_out' => '15:00:00',
            'status' => AttendanceStatusEnum::TERLAMBAT,
            'device_id' => \App\Models\Device::factory()->create()->id,
        ]);

        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function ($to, $message) {
                    return $to === '628222222222' && str_contains($message, 'Cici') && str_contains($message, 'jam 07:50 (20 menit)');
                })
                ->andReturn(['success' => true]);
        });

        $this->artisan('attendance:send-teacher-late-daily --force')
            ->assertExitCode(0);
    }

    public function test_pdf_attachment_configured_falls_back_to_text_when_pdf_disabled_in_tests(): void
    {
        $this->seedCommonSettings(enabled: true, sendTime: '08:00', output: 'pdf_attachment');

        $rule = AttendanceRule::factory()->make(['time_in_end' => '07:30:00']);
        $this->mock(AttendanceService::class, function ($mock) use ($rule) {
            $mock->shouldReceive('getAttendanceRule')->andReturn($rule);
        });

        $t = Teacher::factory()->create(['name' => 'Dedi', 'nip' => '101']);
        TeacherAttendance::create([
            'teacher_id' => $t->id,
            'date' => Carbon::now()->toDateString(),
            'time_in' => '07:35:00', // 5 minutes late
            'time_out' => '15:00:00',
            'status' => AttendanceStatusEnum::TERLAMBAT,
            'device_id' => \App\Models\Device::factory()->create()->id,
        ]);

        // Since PdfReportService::isEnabled() returns false during unit tests, service should fall back to text
        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function ($to, $message) {
                    return $to === '628222222222' && str_contains($message, 'Dedi') && str_contains($message, 'jam 07:35 (5 menit)');
                })
                ->andReturn(['success' => true]);
        });

        $this->artisan('attendance:send-teacher-late-daily')
            ->assertExitCode(0);
    }
}
