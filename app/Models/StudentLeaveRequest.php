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
        'start_date' => 'date',
        'end_date' => 'date',
        'via' => LeaveRequestViaEnum::class,
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
