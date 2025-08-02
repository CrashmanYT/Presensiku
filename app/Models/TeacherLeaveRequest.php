<?php

namespace App\Models;

use App\Enums\LeaveRequestViaEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherLeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'type',
        'start_date',
        'end_date',
        'attachment',
        'reason',
        'submitted_by',
        'via'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'via' => LeaveRequestViaEnum::class
    ];

    public function teacher() {
        return $this->belongsTo(Teacher::class);
    }
}
