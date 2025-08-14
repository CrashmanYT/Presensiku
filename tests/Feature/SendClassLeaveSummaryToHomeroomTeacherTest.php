<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatusEnum;
use App\Helpers\SettingsHelper;
use App\Models\Classes;
use App\Models\Device;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Teacher;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendClassLeaveSummaryToHomeroomTeacherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Freeze time for deterministic tests: 14 Aug 2025, 08:00
        $now = CarbonImmutable::create(2025, 8, 14, 8, 0, 0);
        CarbonImmutable::setTestNow($now);
        Carbon::setTestNow(Carbon::instance($now));
    }

    private function setTimeInEnd(string $time = '08:00'): void
    {
        // Configure the gate time via settings (repository reads from this)
        SettingsHelper::set('attendance.defaults.time_in_end', $time, 'string');
    }

    public function test_skips_when_not_in_send_time_window_and_no_force(): void
    {
        $this->setTimeInEnd('22:00'); // far from 08:00

        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')->never();
        });

        $this->artisan('attendance:send-leave-summary')
            ->assertExitCode(0);
    }

    public function test_outputs_no_students_when_none_in_time_window(): void
    {
        $this->setTimeInEnd('08:00');

        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')->never();
        });

        $this->artisan('attendance:send-leave-summary')
            ->expectsOutput('Starting to process daily leave summaries for homeroom teachers...')
            ->expectsOutput('No students with leave today. Nothing to send.')
            ->assertExitCode(0);
    }

    public function test_sends_summary_per_class_in_time_window(): void
    {
        $this->setTimeInEnd('08:00');

        // Create homeroom teacher with a known WhatsApp number
        $teacher = Teacher::factory()->create(['whatsapp_number' => '628222222222']);
        $classA = Classes::factory()->create([
            'name' => 'X IPA 1',
            'homeroom_teacher_nip' => $teacher->nip,
        ]);

        // Another class with missing WA number to ensure it is skipped
        $teacherNoWa = Teacher::factory()->create(['whatsapp_number' => null]);
        $classB = Classes::factory()->create([
            'name' => 'X IPS 2',
            'homeroom_teacher_nip' => $teacherNoWa->nip,
        ]);

        // Device needed for attendance records
        $device = Device::factory()->create();

        // Two students in class A with leave today
        $s1 = Student::factory()->create(['class_id' => $classA->id, 'name' => 'Ani']);
        $s2 = Student::factory()->create(['class_id' => $classA->id, 'name' => 'Budi']);

        StudentAttendance::create([
            'student_id' => $s1->id,
            'date' => Carbon::now()->toDateString(),
            'status' => AttendanceStatusEnum::IZIN,
            'device_id' => $device->id,
        ]);
        StudentAttendance::create([
            'student_id' => $s2->id,
            'date' => Carbon::now()->toDateString(),
            'status' => AttendanceStatusEnum::SAKIT,
            'device_id' => $device->id,
        ]);

        // One student in class B also on leave but homeroom has no WA -> should be skipped
        $s3 = Student::factory()->create(['class_id' => $classB->id, 'name' => 'Cici']);
        StudentAttendance::create([
            'student_id' => $s3->id,
            'date' => Carbon::now()->toDateString(),
            'status' => AttendanceStatusEnum::IZIN,
            'device_id' => $device->id,
        ]);

        $this->mock(WhatsappService::class, function ($mock) use ($classA) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function ($to, $message) use ($classA) {
                    return $to === '628222222222'
                        && str_contains($message, 'Laporan Izin/Sakit')
                        && str_contains($message, "kelas *{$classA->name}*")
                        && str_contains($message, '- Ani (Izin)')
                        && str_contains($message, '- Budi (Sakit)');
                })
                ->andReturn(['success' => true]);
        });

        $this->artisan('attendance:send-leave-summary')
            ->expectsOutput('Starting to process daily leave summaries for homeroom teachers...')
            ->expectsOutput('Found 2 classes with students on leave. Processing each class...')
            ->expectsOutput('Sending summary to homeroom teacher of X IPA 1...')
            ->expectsOutput('Finished processing daily leave summaries.')
            ->assertExitCode(0);
    }

    public function test_force_bypasses_time_gate_and_sends(): void
    {
        $this->setTimeInEnd('22:00'); // outside window, but we will force

        $teacher = Teacher::factory()->create(['whatsapp_number' => '628333333333']);
        $class = Classes::factory()->create([
            'name' => 'XI IPA 2',
            'homeroom_teacher_nip' => $teacher->nip,
        ]);
        $device = Device::factory()->create();
        $st = Student::factory()->create(['class_id' => $class->id, 'name' => 'Dedi']);
        StudentAttendance::create([
            'student_id' => $st->id,
            'date' => Carbon::now()->toDateString(),
            'status' => AttendanceStatusEnum::IZIN,
            'device_id' => $device->id,
        ]);

        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function ($to, $message) {
                    return $to === '628333333333' && str_contains($message, 'Dedi') && str_contains($message, 'Izin');
                })
                ->andReturn(['success' => true]);
        });

        $this->artisan('attendance:send-leave-summary --force')
            ->assertExitCode(0);
    }
}
