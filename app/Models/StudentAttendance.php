<?php

namespace App\Models;

use App\Enums\AttendanceStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'date',
        'time_in',
        'time_out',
        'status',
        'photo_in',
        'device_id',
    ];

    protected $casts = [
        'date' => 'date',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
        'status' => AttendanceStatusEnum::class
    ];

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function device() {
        return $this->belongsTo(Device::class,);
    }
}
