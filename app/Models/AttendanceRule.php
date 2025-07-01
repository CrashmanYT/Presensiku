<?php

namespace App\Models;

use App\Enums\DayOfWeekEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'day_of_week',
        'date_override',
        'time_in_start',
        'time_in_end',
        'time_out_start',
        'time_out_end',
        'description'
    ];
    protected $casts = [
        'date_override' => 'date',
        'time_in_start' => 'datetime',
        'time_in_end' => 'datetime',
        'time_out_start' => 'datetime',
        'time_out_end' => 'datetime',
        'day_of_week' => 'array'
    ];


    public function class() {
        return $this->belongsTo(Classes::class, 'class_id');
    }
}
