<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'cloud_id',
        'serial_number',
        'type',
        'location',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function studentAttendances() {
        return $this->hasMany(StudentAttendance::class);
    }

    public function teacherAttendances() {
        return $this->hasMany(TeacherAttendance::class);
    }

    public function scanLogs() {
        return $this->hasMany(ScanLog::class);
    }
}
