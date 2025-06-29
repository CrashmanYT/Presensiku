<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_path',
        'restored'
    ];

    protected $casts = [
        'restored' => 'boolean'
    ];
}
