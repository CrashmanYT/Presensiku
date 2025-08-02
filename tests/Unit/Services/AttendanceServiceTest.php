<?php

namespace Tests\Unit\Services;

use App\Enums\AttendanceStatusEnum;
use App\Helpers\SettingsHelper;
use App\Models\AttendanceRule;
use App\Models\Classes;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $settingsHelperMock;
    private AttendanceService $attendanceService;
    private Classes $class;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Mock SettingsHelper untuk mengisolasi ketergantungan eksternal.
        $this->settingsHelperMock = $this->mock(SettingsHelper::class);

        // 2. Buat instance service yang akan diuji.
        $this->attendanceService = new AttendanceService($this->settingsHelperMock);

        // 3. Buat data kelas dummy untuk digunakan di tes.
        $this->class = Classes::factory()->create();
    }

    // --- Tes untuk getAttendanceRule ---

    #[Test]
    public function it_should_return_the_date_override_rule_if_it_exists()
    {
        // Arrange: Siapkan dua aturan yang berpotensi konflik
        $scanDate = Carbon::create(2025, 8, 4); // Hari Senin

        // Aturan umum untuk setiap hari Senin
        AttendanceRule::factory()->create([
            'class_id' => $this->class->id,
            'day_of_week' => ['monday'],
            'description' => 'Aturan Senin Biasa',
        ]);

        // Aturan khusus yang menimpa (override) tanggal tersebut
        $overrideRule = AttendanceRule::factory()->create([
            'class_id' => $this->class->id,
            'date_override' => $scanDate->toDateString(),
            'description' => 'Aturan Khusus Tanggal 4 Agustus',
        ]);

        // Act: Panggil method yang diuji
        $foundRule = $this->attendanceService->getAttendanceRule($this->class->id, $scanDate);

        // Assert: Pastikan aturan yang ditemukan adalah aturan khusus
        $this->assertNotNull($foundRule);
        $this->assertEquals($overrideRule->id, $foundRule->id);
        $this->assertEquals('Aturan Khusus Tanggal 4 Agustus', $foundRule->description);
    }

    #[Test]
    public function it_should_return_the_day_of_week_rule_if_no_override_exists()
    {
        // Arrange
        $scanDate = Carbon::create(2025, 8, 4); // Hari Senin

        $mondayRule = AttendanceRule::factory()->create([
            'class_id' => $this->class->id,
            'day_of_week' => ['monday'],
            'description' => 'Aturan Senin',
        ]);

        // Aturan lain untuk memastikan tidak salah pilih
        AttendanceRule::factory()->create([
            'class_id' => $this->class->id,
            'day_of_week' => ['tuesday'],
            'description' => 'Aturan Selasa',
        ]);

        // Act
        $foundRule = $this->attendanceService->getAttendanceRule($this->class->id, $scanDate);

        // Assert
        $this->assertNotNull($foundRule);
        $this->assertEquals($mondayRule->id, $foundRule->id);
        $this->assertEquals('Aturan Senin', $foundRule->description);
    }

    #[Test]
    public function it_should_return_a_default_rule_from_settings_if_no_specific_rule_is_found()
    {
        // Arrange
        $scanDate = Carbon::create(2025, 8, 4); // Hari Senin

        // Tidak ada aturan yang dibuat di database untuk kelas ini.

        // Atur mock untuk mengembalikan pengaturan default
        $defaultSettings = [
            'time_in_start' => '07:00:00',
            'time_in_end' => '08:00:00',
            'time_out_start' => '14:00:00',
            'time_out_end' => '16:00:00',
        ];
        // Kita gunakan mock yang sudah dibuat di setUp()
        $this->settingsHelperMock->shouldReceive('getAttendanceSettings')->once()->andReturn($defaultSettings);

        // Act
        $foundRule = $this->attendanceService->getAttendanceRule($this->class->id, $scanDate);

        // Assert
        $this->assertNotNull($foundRule);
        $this->assertInstanceOf(AttendanceRule::class, $foundRule);
        $this->assertEquals('07:00:00', $foundRule->time_in_start->format('H:i:s'));
        $this->assertEquals('Jadwal Absensi Bawaan', $foundRule->description);
    }

    // --- Tes untuk checkAttendanceStatus ---

    #[Test]
    public function it_should_return_on_time_status_if_scan_is_within_the_allowed_time_range()
    {
        // Arrange
        $scanTime = Carbon::create(2025, 8, 4, 7, 30, 0); // Scan jam 07:30
        $rule = AttendanceRule::factory()->make([ // Gunakan 'make' karena kita tidak perlu menyimpannya
            'time_in_start' => '07:00:00',
            'time_in_end' => '08:00:00',
            'day_of_week' => ['monday'],
        ]);

        // Act
        $status = $this->attendanceService->checkAttendanceStatus($scanTime, $rule);

        // Assert
        $this->assertEquals(AttendanceStatusEnum::HADIR, $status);
    }

    #[Test]
    public function it_should_return_late_status_if_scan_is_outside_the_allowed_time_range()
    {
        // Arrange
        $scanTime = Carbon::create(2025, 8, 4, 8, 1, 0); // Scan jam 08:01, 1 menit setelah batas akhir
        $rule = AttendanceRule::factory()->make([
            'time_in_start' => '07:00:00',
            'time_in_end' => '08:00:00',
            'day_of_week' => ['monday'],
        ]);

        // Act
        $status = $this->attendanceService->checkAttendanceStatus($scanTime, $rule);

        // Assert
        $this->assertEquals(AttendanceStatusEnum::TERLAMBAT, $status);
    }
}
