<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Teacher;
use App\Models\TeacherAttendance;

class UserFinderService
{
    public function findByFingerprint(string $fingerprintId)
    {
        return Student::where('fingerprint_id', $fingerprintId)->first()
            ?? Teacher::where('fingerprint_id', $fingerprintId)->first();
    }

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

    public function findTestStudent()
    {
        return Student::has('attendances')
            ->whereNotNull('fingerprint_id')
            ->first();
    }
}
