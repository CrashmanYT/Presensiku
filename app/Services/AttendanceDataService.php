<?php

namespace App\Services;

use App\Models\AttendanceRule;
use App\Models\Holiday;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AttendanceDataService
{
    public function fetchAttendances(Student | Teacher $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $dateRange = [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')];
        $query = $user instanceof Student
            ? StudentAttendance::where('student_id', $user->id)
            : TeacherAttendance::where('teacher_id', $user->id);

        return $query->whereBetween('date', $dateRange)
            ->get()
            ->keyBy(fn ($item) => $item->date->format('Y-m-d'));
    }

    public function fetchHolidays(Carbon $startDate, Carbon $endDate): Collection
    {
        $startDateStr = $startDate->format('Y-m-d');
        $endDateStr = $endDate->format('Y-m-d');

        return Holiday::where(function ($query) use ($startDateStr, $endDateStr) {
            $query->whereBetween('start_date', [$startDateStr, $endDateStr])
                  ->orWhereBetween('end_date', [$startDateStr, $endDateStr])
                  ->orWhere(function ($q) use ($startDateStr, $endDateStr) {
                      $q->where('start_date', '<=', $startDateStr)
                        ->where('end_date', '>=', $endDateStr);
                  });
        })->get();
    }

    public function fetchAttendanceRules(Student | Teacher $user)
    {
        if (!($user instanceof Student) || !$user->class) {
            return collect();
        }

        return AttendanceRule::where('class_id', $user->class->id)->get();
    }
}
