<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use App\Models\Holiday;
use App\Models\AttendanceRule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceCalendarService
{
    public function buildCalendar($user, int $year, int $month): array
    {
        if (!$user) return [];

        [$startDate, $endDate] = $this->getMonthDateRange($year, $month);
        
        $attendances = $this->fetchAttendances($user, $startDate, $endDate);
        $holidays = $this->fetchHolidays($startDate, $endDate);
        $attendanceRules = $this->fetchAttendanceRules($user);
        
        return $this->buildCalendarDays($startDate, $month, $attendances, $holidays, $attendanceRules);
    }

    private function getMonthDateRange(int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        return [$startDate, $endDate];
    }

    private function fetchAttendances($user, Carbon $startDate, Carbon $endDate): Collection
    {
        $dateRange = [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')];
        
        if ($user instanceof Student) {
            $attendances = StudentAttendance::where('student_id', $user->id)
                ->whereBetween('date', $dateRange)
                ->get();
        } else {
            $attendances = TeacherAttendance::where('teacher_id', $user->id)
                ->whereBetween('date', $dateRange)
                ->get();
        }

        return $attendances->keyBy(fn($item) => $item->date->format('Y-m-d'));
    }

    private function fetchHolidays(Carbon $startDate, Carbon $endDate): Collection
    {
        return Holiday::where(function ($query) use ($startDate, $endDate) {
            $startDateStr = $startDate->format('Y-m-d');
            $endDateStr = $endDate->format('Y-m-d');
            
            $query->whereBetween('start_date', [$startDateStr, $endDateStr])
                  ->orWhereBetween('end_date', [$startDateStr, $endDateStr])
                  ->orWhere(function ($q) use ($startDateStr, $endDateStr) {
                      $q->where('start_date', '<=', $startDateStr)
                        ->where('end_date', '>=', $endDateStr);
                  });
        })->get();
    }

    private function fetchAttendanceRules($user): Collection
    {
        if (!($user instanceof Student) || !$user->class) {
            return collect();
        }
        
        return AttendanceRule::where('class_id', $user->class->id)->get();
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
        
        // Check for specific date override
        if ($attendanceRules->contains(fn($rule) => $rule->date_override && $rule->date_override->format('Y-m-d') === $dateString)) {
            return true;
        }
        
        // Check for day of week rules
        return $attendanceRules->contains(fn($rule) => 
            $rule->day_of_week && 
            is_array($rule->day_of_week) && 
            in_array($dayName, $rule->day_of_week)
        );
    }
}
