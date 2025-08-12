<?php

namespace App\Models;

use App\Enums\LeaveRequestViaEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentLeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'attachment',
        'submitted_by',
        'via',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'via' => LeaveRequestViaEnum::class,
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function setStartDateAttribute($value)
    {
        $this->attributes['start_date'] = $this->parseDate($value);
    }

    public function setEndDateAttribute($value)
    {
        $this->attributes['end_date'] = $this->parseDate($value);
    }

    /**
     * Parse a date string to Y-m-d format
     */
    protected function parseDate($value)
    {
        if ($value instanceof \Carbon\Carbon) {
            return $value->format('Y-m-d');
        }
        
        if (is_string($value)) {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        }
        
        return $value;
    }
}
