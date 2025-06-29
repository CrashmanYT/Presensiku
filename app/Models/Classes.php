<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{

    use HasFactory;
    
    protected $table = 'classes';

    protected $fillable = [
        'name',
        'level',
        'major',
        'homeroom_teacher_id'
    ];

    public function homeroomTeacher() {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }

    public function students() {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function attendanceRules() {
        return $this->hasMany(AttendanceRule::class, 'class_id');
    }

    public function users() {
        return $this->hasMany(User::class, 'class_id');
    }
}
