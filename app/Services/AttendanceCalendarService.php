<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceCalendarService
{
    /**
     * Builds the calendar structure for a given month using pre-fetched data.
     */
    public function generateCalendar(int $year, int $month, Collection $attendances, Collection $holidays, Collection $attendanceRules): array
    {
        $startDate = Carbon::create($year, $month, 1);
        return $this->buildCalendarDays($startDate, $month, $attendances, $holidays, $attendanceRules);
    }

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

    private function extractStatusValue($status): string
    {
        if ($status instanceof \BackedEnum) {
            return $status->value;
        }
        return (string)$status;
    }

    private function determineNoAttendanceStatus(Carbon $date, Collection $holidays, Collection $attendanceRules): string
    {
        $isHoliday = $this->isHoliday($date, $holidays);
        $shouldHaveAttendance = $this->shouldHaveAttendance($date, $attendanceRules);

        return ($isHoliday || !$shouldHaveAttendance) ? 'holiday' : 'no_data';
    }

    private function isHoliday(Carbon $date, Collection $holidays): bool
    {
        return $holidays->contains(fn($holiday) => $date->between($holiday->start_date, $holiday->end_date));
    }

    private function shouldHaveAttendance(Carbon $date, Collection $attendanceRules): bool
    {
        if ($attendanceRules->isEmpty()) {
            return !$date->isWeekend();
        }

        $dayName = strtolower($date->format('l'));
        $dateString = $date->format('Y-m-d');

        if ($this->hasDateOverrideRule($attendanceRules, $dateString)) {
            return true;
        }

        return $this->hasDayOfWeekRule($attendanceRules, $dayName);
    }

    private function hasDateOverrideRule(Collection $attendanceRules, string $dateString): bool
    {
        return $attendanceRules->contains(fn($rule) => $rule->date_override && $rule->date_override->format('Y-m-d') === $dateString);
    }

    private function hasDayOfWeekRule(Collection $attendanceRules, string $dayName): bool
    {
        return $attendanceRules->contains(fn($rule) =>
            $rule->day_of_week &&
            is_array($rule->day_of_week) &&
            in_array($dayName, $rule->day_of_week)
        );
    }
}
