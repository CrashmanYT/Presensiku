<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'nip',
        'fingerprint_id',
        'photo',
        'whatsapp_number'
    ];

    public function classes() {
        return $this->hasMany(Classes::class);
    }

    public function attendances() {
        return $this->hasMany(TeacherAttendance::class);
    } 

    public function leaveRequests() {
        return $this->hasMany(TeacherLeaveRequest::class);
    }

}
