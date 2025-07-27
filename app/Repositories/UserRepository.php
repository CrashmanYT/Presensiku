<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Models\Student;
use App\Models\Teacher;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Find user by fingerprint ID (Student or Teacher)
     */
    public function findByFingerprintId(string $fingerprintId): Student|Teacher|null
    {
        // Try to find student first
        $student = $this->findStudentByFingerprintId($fingerprintId);
        if ($student) {
            return $student;
        }

        // If no student found, try to find teacher
        return $this->findTeacherByFingerprintId($fingerprintId);
    }

    /**
     * Find student by fingerprint ID
     */
    public function findStudentByFingerprintId(string $fingerprintId): ?Student
    {
        return Student::where('fingerprint_id', $fingerprintId)
            ->with(['class'])
            ->first();
    }

    /**
     * Find teacher by fingerprint ID
     */
    public function findTeacherByFingerprintId(string $fingerprintId): ?Teacher
    {
        return Teacher::where('fingerprint_id', $fingerprintId)->first();
    }
}
