<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisciplineRanking extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'month',
        'total_present',
        'total_absent',
        'total_late',
        'score',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
