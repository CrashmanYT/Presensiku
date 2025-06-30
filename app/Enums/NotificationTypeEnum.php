<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NotificationTypeEnum: string implements HasColor, HasLabel
{
    case IZIN = 'izin';
    case TIDAK_HADIR = 'tidak_hadir';
    case KETERLAMBATAN = 'keterlambatan';
    case REKAP = 'rekap'; 

    public function getLabel(): ?string
    {
        return match($this) {
            self::IZIN => 'Izin',
            self::KETERLAMBATAN => 'Keterlambatan',
            self::TIDAK_HADIR => 'Tidak Hadir',
            self::REKAP => "Rekapitulasi"
        };
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::IZIN => Color::Blue,
            self::KETERLAMBATAN => Color::Yellow,
            self::TIDAK_HADIR => Color::Red,
            self::REKAP => Color::Green,
        };
    }
}
