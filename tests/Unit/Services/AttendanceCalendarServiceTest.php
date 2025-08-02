<?php

namespace Tests\Unit\Services;

use App\Enums\DayOfWeekEnum;
use App\Enums\AttendanceStatusEnum;
use App\Models\AttendanceRule;
use App\Models\Holiday;
use App\Models\StudentAttendance;
use App\Services\AttendanceCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttendanceCalendarServiceTest extends TestCase
{
    private AttendanceCalendarService $calendarService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendarService = new AttendanceCalendarService();
    }

    #[Test]
    public function it_correctly_assigns_status_from_attendance_data()
    {
        // Arrange
        $year = 2025;
        $month = 8; // Agustus 2025
        $dayWithAttendance = 4; // Senin, 4 Agustus 2025

        // Buat collection absensi palsu
        $attendances = new Collection([
            '2025-08-04' => new StudentAttendance([
                'status' => AttendanceStatusEnum::HADIR,
            ])
        ]);

        // Collection lain bisa kosong karena tidak relevan untuk tes ini
        $holidays = new Collection();
        $attendanceRules = new Collection();

        // Act
        $calendar = $this->calendarService->generateCalendar($year, $month, $attendances, $holidays, $attendanceRules);

        // Assert
        $day = $this->findDay($calendar, $dayWithAttendance);
        $this->assertNotNull($day);
        $this->assertEquals(AttendanceStatusEnum::HADIR->value, $day['status']);
    }

    #[Test]
    public function it_assigns_holiday_status_for_a_national_holiday_with_no_attendance()
    {
        // Arrange
        $year = 2025;
        $month = 8;
        $holidayDay = 17; // Minggu, 17 Agustus 2025 - Hari Kemerdekaan

        $attendances = new Collection();
        $holidays = new Collection([
            new Holiday([
                'start_date' => Carbon::create($year, $month, $holidayDay),
                'end_date' => Carbon::create($year, $month, $holidayDay),
            ])
        ]);
        $attendanceRules = new Collection();

        // Act
        $calendar = $this->calendarService->generateCalendar($year, $month, $attendances, $holidays, $attendanceRules);

        // Assert
        $day = $this->findDay($calendar, $holidayDay);
        $this->assertNotNull($day);
        $this->assertEquals('holiday', $day['status']);
    }

    #[Test]
    public function it_assigns_no_data_status_for_a_working_day_with_no_attendance_record()
    {
        // Arrange
        $year = 2025;
        $month = 8; // Agustus 2025
        $workingDay = 4; // Senin, 4 Agustus 2025

        $attendances = new Collection();
        $holidays = new Collection();
        $attendanceRules = new Collection([
            new AttendanceRule([
                'day_of_week' => [DayOfWeekEnum::MONDAY->value],
            ])
        ]);

        // Act
        $calendar = $this->calendarService->generateCalendar($year, $month, $attendances, $holidays, $attendanceRules);

        // Assert
        $day = $this->findDay($calendar, $workingDay);
        $this->assertNotNull($day);
        $this->assertEquals('no_data', $day['status']);
    }

    #[Test]
    public function it_assigns_holiday_status_for_a_non_working_day_like_sunday()
    {
        // Arrange
        $year = 2025;
        $month = 8; // Agustus 2025
        $sunday = 3; // Minggu, 3 Agustus 2025

        $attendances = new Collection();
        $holidays = new Collection();
        $attendanceRules = new Collection([
            new AttendanceRule([
                'day_of_week' => [DayOfWeekEnum::MONDAY->value],
            ])
        ]);

        // Act
        $calendar = $this->calendarService->generateCalendar($year, $month, $attendances, $holidays, $attendanceRules);

        // Assert
        $day = $this->findDay($calendar, $sunday);
        $this->assertNotNull($day);
        $this->assertEquals('holiday', $day['status']);
    }

    /**
     * Helper function to find a specific day from the calendar array.
     */
    private function findDay(array $calendar, int $day): ?array
    {
        foreach ($calendar as $date) {
            if ($date['date'] === $day) {
                return $date;
            }
        }
        return null;
    }
}
