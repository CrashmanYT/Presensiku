<?php

namespace App\Models;

use App\Enums\AttendanceStatusEnum;
use App\Interfaces\AttendanceInterface;
use App\Models\AttendanceRule;
use Carbon\Carbon;
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

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function device(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function detectScanType(Carbon $scanTime, AttendanceRule $attendanceRule): ?string
    {
        $scanTimeFormatted = $scanTime->format('H:i:s');
        $timeInStart = Carbon::parse($attendanceRule->time_in_start)->format('H:i:s');
        $timeInEnd = Carbon::parse($attendanceRule->time_in_end)->format('H:i:s');
        $timeOutStart = Carbon::parse($attendanceRule->time_out_start)->format('H:i:s');
        $timeOutEnd = Carbon::parse($attendanceRule->time_out_end)->format('H:i:s');

        // If no time_in recorded yet and scan time is within check-in window
        if (!$this->time_in && $scanTimeFormatted >= $timeInStart && $scanTimeFormatted <= $timeInEnd) {
            return 'in';
        }

        // If time_in exists but no time_out and scan time is within check-out window
        if ($this->time_in && !$this->time_out && $scanTimeFormatted >= $timeOutStart && $scanTimeFormatted <= $timeOutEnd) {
            return 'out';
        }

        return null;
    }
}
