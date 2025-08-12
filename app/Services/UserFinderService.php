<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Teacher;
use App\Models\TeacherAttendance;

/**
 * Utility service for locating users and their most recent attendance entries.
 */
class UserFinderService
{
    /**
     * Find a student or teacher by fingerprint id.
     *
     * @param string $fingerprintId
     * @return Student|Teacher|null
     */
    public function findByFingerprint(string $fingerprintId)
    {
        return Student::where('fingerprint_id', $fingerprintId)->first()
            ?? Teacher::where('fingerprint_id', $fingerprintId)->first();
    }

    /**
     * Find the most recent attendance record across students and teachers.
     *
     * If $lastTimestamp is provided, only consider records created after that timestamp.
     *
     * @param string|null $lastTimestamp ISO datetime string or null
     * @return StudentAttendance|TeacherAttendance|null
     */
    public function findMostRecentAttendance(?string $lastTimestamp = null)
    {
        $latestStudentAttendance = StudentAttendance::with('student:id,name,fingerprint_id')
            ->when($lastTimestamp, fn ($query) => $query->where('created_at', '>', $lastTimestamp))
            ->orderBy('created_at', 'desc')
            ->first();

        $latestTeacherAttendance = TeacherAttendance::with('teacher:id,name,fingerprint_id')
            ->when($lastTimestamp, fn ($query) => $query->where('created_at', '>', $lastTimestamp))
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $latestStudentAttendance) {
            return $latestTeacherAttendance;
        }
        if (! $latestTeacherAttendance) {
            return $latestStudentAttendance;
        }

        return $latestTeacherAttendance->created_at->isAfter($latestStudentAttendance->created_at)
            ? $latestTeacherAttendance
            : $latestStudentAttendance;
    }

    /**
     * Find a test student with at least one attendance record and a fingerprint id.
     *
     * @return Student|null
     */
    public function findTestStudent()
    {
        return Student::has('attendances')
            ->whereNotNull('fingerprint_id')
            ->first();
    }
}
