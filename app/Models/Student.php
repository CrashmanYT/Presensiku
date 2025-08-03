<?php

namespace App\Models;

use App\Enums\GenderEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Student extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'nis',
        'class_id',
        'gender',
        'fingerprint_id',
        'photo',
        'parent_whatsapp',
    ];

    protected $casts = [
        'gender' => GenderEnum::class,
    ];

    public function class()
    {
        return $this->belongsTo(Classes::class);
    }

    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(StudentLeaveRequest::class);
    }

    public function notifications()
    {
        return $this->morphMany(\Illuminate\Notifications\DatabaseNotification::class, 'notifiable');
    }

    public function disciplineRankings()
    {
        return $this->hasMany(DisciplineRanking::class);
    }
}
