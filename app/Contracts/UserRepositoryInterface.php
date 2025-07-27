<?php

namespace App\Contracts;

use App\Models\Student;
use App\Models\Teacher;

interface UserRepositoryInterface
{
    /**
     * Find user by fingerprint ID
     */
    public function findByFingerprintId(string $fingerprintId): Student|Teacher|null;

    /**
     * Find student by fingerprint ID
     */
    public function findStudentByFingerprintId(string $fingerprintId): ?Student;

    /**
     * Find teacher by fingerprint ID
     */
    public function findTeacherByFingerprintId(string $fingerprintId): ?Teacher;
}
