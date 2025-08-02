<?php

namespace App\Models;

use App\Enums\EventTypeEnum;
use App\Enums\ScanResultEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'fingerprint_id',
        'event_type',
        'scanned_at',
        'device_id',
        'result',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'event_type' => EventTypeEnum::class,
        'result' => ScanResultEnum::class,
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
