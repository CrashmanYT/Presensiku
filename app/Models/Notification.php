<?php

namespace App\Models;

use App\Enums\NotificationRecipientEnum;
use App\Enums\NotificationStatusEnum;
use App\Enums\NotificationTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'type',
        'recipient',
        'content',
        'status',
        'sent_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'type' => NotificationTypeEnum::class,
        'recipient' => NotificationRecipientEnum::class,
        'status' => NotificationStatusEnum::class
    ];

    public function students() {
        return $this->hasMany(Student::class);
    }
}
