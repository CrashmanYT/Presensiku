<?php

namespace Tests\Unit\Services;

use App\Enums\AttendanceStatusEnum;
use App\Models\AttendanceRule;
use App\Models\Device;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Services\AttendanceProcessingService;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttendanceProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $attendanceServiceMock;

    private AttendanceProcessingService $attendanceProcessingService;

    private Student $student;

    private Device $device;

    private AttendanceRule $rule;

    /**
     * Menyiapkan environment untuk setiap tes.
     * Method ini dijalankan sebelum setiap method tes.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Membuat "Mock" atau objek palsu dari AttendanceService.
        // Ini memungkinkan kita mengontrol output dari methodnya (spt. getAttendanceRule)
        // tanpa benar-benar menjalankan logika di dalam AttendanceService.
        $this->attendanceServiceMock = $this->mock(AttendanceService::class);

        // 2. Membuat instance dari service yang akan kita tes, dengan menyuntikkan mock di atas.
        $this->attendanceProcessingService = new AttendanceProcessingService($this->attendanceServiceMock);

        // 3. Membuat data dummy yang akan sering digunakan di banyak tes.
        $this->student = Student::factory()->create();
        $this->device = Device::factory()->create();

        // 4. Membuat aturan absensi standar untuk tes.
        // Misal: Masuk jam 07:00, batas akhir masuk 07:10, pulang mulai 14:00
        $this->rule = AttendanceRule::factory()->create([
            'time_in_start' => '07:00:00',
            'time_in_end' => '07:10:00',
            'time_out_start' => '14:00:00',
            'time_out_end' => '16:00:00',
        ]);
    }

    #[Test]
    public function it_should_create_new_attendance_for_the_first_scan_of_the_day()
    {
        // Arrange: Siapkan kondisi tes
        $scanTime = Carbon::create(2025, 8, 1, 7, 5, 0); // Scan jam 07:05:00

        // Atur ekspektasi untuk mock:
        // - getAttendanceRule akan dipanggil sekali dan harus mengembalikan aturan standar kita.
        // - checkAttendanceStatus akan dipanggil sekali dan harus mengembalikan status 'hadir'.
        $this->attendanceServiceMock->shouldReceive('getAttendanceRule')->once()->andReturn($this->rule);
        $this->attendanceServiceMock->shouldReceive('checkAttendanceStatus')->once()->andReturn(AttendanceStatusEnum::HADIR);

        // Act: Jalankan method yang dites
        $response = $this->attendanceProcessingService->handleScan($this->student, $this->device, $scanTime);

        // Assert: Verifikasi hasilnya
        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $this->student->id,
            'date' => $scanTime->toDateString(),
            'time_in' => $scanTime->toTimeString(),
            'time_out' => null, // Pastikan time_out masih kosong
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('success', $response->getData()->status);
        $this->assertEquals('Scan masuk berhasil', $response->getData()->message);
    }

    #[Test]
    public function it_should_set_status_to_on_time_for_a_timely_check_in()
    {
        // Arrange
        $scanTime = Carbon::create(2025, 8, 1, 7, 9, 0); // Scan jam 07:09:00 (masih dalam batas waktu)

        $this->attendanceServiceMock->shouldReceive('getAttendanceRule')->once()->andReturn($this->rule);
        // Secara eksplisit kita katakan bahwa untuk scanTime ini, statusnya adalah HADIR
        $this->attendanceServiceMock->shouldReceive('checkAttendanceStatus')
            ->with($scanTime, $this->rule)
            ->once()
            ->andReturn(AttendanceStatusEnum::HADIR);

        // Act
        $this->attendanceProcessingService->handleScan($this->student, $this->device, $scanTime);

        // Assert
        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $this->student->id,
            'date' => $scanTime->toDateString(),
            'status' => AttendanceStatusEnum::HADIR->value, // Verifikasi statusnya adalah 'hadir'
        ]);
    }

    #[Test]
    public function it_should_set_status_to_late_for_a_late_check_in()
    {
        // Arrange
        $scanTime = Carbon::create(2025, 8, 1, 7, 11, 0); // Scan jam 07:11:00 (terlambat 1 menit)

        $this->attendanceServiceMock->shouldReceive('getAttendanceRule')->once()->andReturn($this->rule);
        // Ekspektasi: untuk scanTime ini, statusnya adalah TERLAMBAT
        $this->attendanceServiceMock->shouldReceive('checkAttendanceStatus')
            ->with($scanTime, $this->rule)
            ->once()
            ->andReturn(AttendanceStatusEnum::TERLAMBAT);

        // Act
        $this->attendanceProcessingService->handleScan($this->student, $this->device, $scanTime);

        // Assert
        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $this->student->id,
            'date' => $scanTime->toDateString(),
            'status' => AttendanceStatusEnum::TERLAMBAT->value, // Verifikasi statusnya adalah 'terlambat'
        ]);
    }

    #[Test]
    public function it_should_process_a_checkout_if_user_has_already_checked_in()
    {
        // Arrange: Buat dulu data absensi masuk di database
        $checkInTime = Carbon::create(2025, 8, 1, 7, 5, 0);
        StudentAttendance::factory()->create([
            'student_id' => $this->student->id,
            'date' => $checkInTime->toDateString(),
            'time_in' => $checkInTime->toTimeString(),
            'time_out' => null, // Pastikan time_out kosong
        ]);

        $scanTime = Carbon::create(2025, 8, 1, 14, 5, 0); // Scan pulang jam 14:05:00

        $this->attendanceServiceMock->shouldReceive('getAttendanceRule')->once()->andReturn($this->rule);
        // Perhatikan: checkAttendanceStatus TIDAK dipanggil saat scan pulang, jadi kita tidak set ekspektasi.

        // Act
        $response = $this->attendanceProcessingService->handleScan($this->student, $this->device, $scanTime);

        // Assert
        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $this->student->id,
            'date' => $scanTime->toDateString(),
            'time_in' => $checkInTime->toTimeString(),
            'time_out' => $scanTime->toTimeString(), // Verifikasi time_out sudah terisi
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Scan keluar berhasil', $response->getData()->message);
    }

    #[Test]
    public function it_should_return_already_checked_in_message_for_duplicate_scan_before_checkout_time()
    {
        // Arrange: Buat data absensi masuk
        $checkInTime = Carbon::create(2025, 8, 1, 7, 5, 0);
        StudentAttendance::factory()->create([
            'student_id' => $this->student->id,
            'date' => $checkInTime->toDateString(),
            'time_in' => $checkInTime->toTimeString(),
            'time_out' => null, // Secara eksplisit set time_out ke null
        ]);

        // Scan lagi di jam 10 pagi, sebelum jam pulang (14:00)
        $scanTime = Carbon::create(2025, 8, 1, 10, 0, 0);

        $this->attendanceServiceMock->shouldReceive('getAttendanceRule')->once()->andReturn($this->rule);

        // Act
        $response = $this->attendanceProcessingService->handleScan($this->student, $this->device, $scanTime);

        // Assert
        $this->assertEquals(409, $response->getStatusCode()); // 409 Conflict
        $this->assertEquals('info', $response->getData()->status);
        $this->assertEquals('Anda sudah melakukan absensi masuk', $response->getData()->message);

        // Pastikan tidak ada data yang berubah di database
        $this->assertDatabaseCount('student_attendances', 1);
        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $this->student->id,
            'time_out' => null,
        ]);
    }

    #[Test]
    public function it_should_return_already_completed_message_if_user_has_already_checked_out()
    {
        // Arrange: Buat data absensi yang sudah lengkap (masuk dan pulang)
        $checkInTime = Carbon::create(2025, 8, 1, 7, 5, 0);
        $checkOutTime = Carbon::create(2025, 8, 1, 14, 5, 0);
        StudentAttendance::factory()->create([
            'student_id' => $this->student->id,
            'date' => $checkInTime->toDateString(),
            'time_in' => $checkInTime->toTimeString(),
            'time_out' => $checkOutTime->toTimeString(), // time_out sudah terisi
        ]);

        // Scan lagi setelah absen lengkap
        $scanTime = Carbon::create(2025, 8, 1, 15, 0, 0);

        $this->attendanceServiceMock->shouldReceive('getAttendanceRule')->once()->andReturn($this->rule);

        // Act
        $response = $this->attendanceProcessingService->handleScan($this->student, $this->device, $scanTime);

        // Assert
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals('info', $response->getData()->status);
        $this->assertEquals('Anda sudah melakukan scan masuk dan keluar hari ini', $response->getData()->message);

        // Pastikan tidak ada data yang berubah
        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $this->student->id,
            'time_out' => $checkOutTime->toTimeString(),
        ]);
    }
}
