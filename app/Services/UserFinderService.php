<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\StudentAttendance;

class UserFinderService
{
    public function findByFingerprint(string $fingerprintId)
    {
        return Student::where('fingerprint_id', $fingerprintId)->first()
            ?? Teacher::where('fingerprint_id', $fingerprintId)->first();
    }

    public function findRecentAttendance(?int $lastScanId = null)
    {
        return StudentAttendance::where('created_at', '>=', now()->subSeconds(3))
            ->with(['student:id,name,fingerprint_id'])
            ->when($lastScanId, fn($query) => $query->where('id', '>', $lastScanId))
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function findTestStudent()
    {
        return Student::has('attendances')
            ->whereNotNull('fingerprint_id')
            ->first();
    }
}
