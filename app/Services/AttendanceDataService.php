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

/**
 * Data access service for attendance-related queries used by calendar and reports.
 */
class AttendanceDataService
{
    /**
     * Fetch attendances for a user within a date range and key them by date (Y-m-d).
     *
     * @param Student|Teacher $user Target user
     * @param Carbon $startDate Inclusive start date
     * @param Carbon $endDate Inclusive end date
     * @return Collection<string, StudentAttendance|TeacherAttendance> Collection keyed by Y-m-d
     */
    public function fetchAttendances(Student|Teacher $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $dateRange = [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')];
        $query = $user instanceof Student
            ? StudentAttendance::where('student_id', $user->id)
            : TeacherAttendance::where('teacher_id', $user->id);

        return $query->whereBetween('date', $dateRange)
            ->get()
            ->keyBy(fn ($item) => $item->date->format('Y-m-d'));
    }

    /**
     * Fetch holidays that overlap with the provided date range.
     *
     * A holiday overlaps if its start/end falls within the range, or it spans across the range.
     *
     * @param Carbon $startDate Inclusive start date
     * @param Carbon $endDate Inclusive end date
     * @return Collection<int, Holiday>
     */
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

    /**
     * Fetch attendance rules for the student's class (if applicable).
     *
     * Teachers or students without class will return an empty collection.
     *
     * @param Student|Teacher $user Target user
     * @return Collection<int, AttendanceRule>
     */
    public function fetchAttendanceRules(Student|Teacher $user)
    {
        if (! ($user instanceof Student) || ! $user->class) {
            return collect();
        }

        return AttendanceRule::where('class_id', $user->class->id)->get();
    }
}
