<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds month calendar views for attendance, using pre-fetched attendances,
 * holidays, and attendance rules.
 */
class AttendanceCalendarService
{
    /**
     * Build the calendar structure for a given month using pre-fetched data.
     *
     * @param int $year Target year (e.g., 2025)
     * @param int $month Target month (1-12)
     * @param Collection $attendances Map keyed by Y-m-d to attendance model
     * @param Collection $holidays Collection of holiday models
     * @param Collection $attendanceRules Collection of attendance rule models
     * @return array<int, array{date:int,full_date:string,status:string,is_today:bool,is_weekend:bool}>
     */
    public function generateCalendar(int $year, int $month, Collection $attendances, Collection $holidays, Collection $attendanceRules): array
    {
        $startDate = Carbon::create($year, $month, 1);

        return $this->buildCalendarDays($startDate, $month, $attendances, $holidays, $attendanceRules);
    }

    /**
     * Iterate days in month and compute status per day.
     *
     * @param Carbon $startDate First day of target month
     * @param int $month Month number (1-12)
     * @param Collection $attendances Map keyed by Y-m-d
     * @param Collection $holidays Holiday models
     * @param Collection $attendanceRules Attendance rule models
     * @return array<int, array{date:int,full_date:string,status:string,is_today:bool,is_weekend:bool}>
     */
    private function buildCalendarDays(Carbon $startDate, int $month, Collection $attendances, Collection $holidays, Collection $attendanceRules): array
    {
        $calendar = [];
        $currentDate = $startDate->copy();

        while ($currentDate->month == $month) {
            $dateString = $currentDate->format('Y-m-d');
            $attendance = $attendances->get($dateString);

            $status = $attendance
                ? $this->extractStatusValue($attendance->status)
                : $this->determineNoAttendanceStatus($currentDate, $holidays, $attendanceRules);

            $calendar[] = [
                'date' => $currentDate->day,
                'full_date' => $dateString,
                'status' => $status,
                'is_today' => $currentDate->isToday(),
                'is_weekend' => $currentDate->isWeekend(),
            ];

            $currentDate->addDay();
        }

        return $calendar;
    }

    /**
     * Extract string value from enum or scalar status.
     *
     * @param mixed $status BackedEnum|string|int
     * @return string
     */
    private function extractStatusValue($status): string
    {
        if ($status instanceof \BackedEnum) {
            return $status->value;
        }

        return (string) $status;
    }

    /**
     * Decide status for days without attendance based on holidays and rules.
     *
     * @param Carbon $date
     * @param Collection $holidays
     * @param Collection $attendanceRules
     * @return string 'holiday' or 'no_data'
     */
    private function determineNoAttendanceStatus(Carbon $date, Collection $holidays, Collection $attendanceRules): string
    {
        $isHoliday = $this->isHoliday($date, $holidays);
        $shouldHaveAttendance = $this->shouldHaveAttendance($date, $attendanceRules);

        return ($isHoliday || ! $shouldHaveAttendance) ? 'holiday' : 'no_data';
    }

    /**
     * Check whether date falls into any holiday range.
     *
     * @param Carbon $date
     * @param Collection $holidays
     * @return bool
     */
    private function isHoliday(Carbon $date, Collection $holidays): bool
    {
        return $holidays->contains(fn ($holiday) => $date->between($holiday->start_date, $holiday->end_date));
    }

    /**
     * Determine if attendance is expected for the given date based on rules.
     *
     * @param Carbon $date
     * @param Collection $attendanceRules
     * @return bool
     */
    private function shouldHaveAttendance(Carbon $date, Collection $attendanceRules): bool
    {
        if ($attendanceRules->isEmpty()) {
            return ! $date->isWeekend();
        }

        $dayName = strtolower($date->format('l'));
        $dateString = $date->format('Y-m-d');

        if ($this->hasDateOverrideRule($attendanceRules, $dateString)) {
            return true;
        }

        return $this->hasDayOfWeekRule($attendanceRules, $dayName);
    }

    /**
     * True when any rule has an explicit override for the date.
     *
     * @param Collection $attendanceRules
     * @param string $dateString Y-m-d
     * @return bool
     */
    private function hasDateOverrideRule(Collection $attendanceRules, string $dateString): bool
    {
        return $attendanceRules->contains(fn ($rule) => $rule->date_override && $rule->date_override->format('Y-m-d') === $dateString);
    }

    /**
     * True when any rule includes the given day of week.
     *
     * @param Collection $attendanceRules
     * @param string $dayName Lowercase day name
     * @return bool
     */
    private function hasDayOfWeekRule(Collection $attendanceRules, string $dayName): bool
    {
        return $attendanceRules->contains(fn ($rule) => $rule->day_of_week &&
            is_array($rule->day_of_week) &&
            in_array($dayName, $rule->day_of_week)
        );
    }
}
