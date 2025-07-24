<?php

namespace App\Models;

use App\Enums\AttendanceStatusEnum;
use App\Interfaces\AttendanceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model implements AttendanceInterface
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
        if (!$this->time_in) {
            if ($scanTime->toDateTime()->format('H:i:s') > $this->time_in
                && $scanTime->toDateTime()->format('H:i:s') < $this->time_out) {
                return 'in';
            }
        }
        if (!$this->time_out) {
            if ($scanTime->toDateTime()->format('H:i:s') > $this->time_in
                && $scanTime->toDateTime()->format('H:i:s') < $this->time_out) {
                return 'out';
            }
        }
        return null;
    }
}
