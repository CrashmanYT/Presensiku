<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AttendanceStatusEnum: string implements HasColor, HasLabel, HasIcon
{
    case HADIR = 'hadir';
    case TERLAMBAT = 'terlambat';
    case TIDAK_HADIR = 'tidak_hadir';
    case IZIN = 'izin';
    case SAKIT = 'sakit';

    public function getLabel(): ?string
    {
        return match($this) {
            self::HADIR => 'Hadir',
            self::TERLAMBAT => 'Terlambat',
            self::TIDAK_HADIR => 'Tidak Hadir',
            self::IZIN => 'Izin',
            self::SAKIT => 'Sakit'
        };
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::HADIR => Color::Green,
            self::TERLAMBAT => Color::Yellow,
            self::TIDAK_HADIR => Color::Red,
            self::IZIN => Color::Blue,
            self::SAKIT => Color::Sky
        };
    }

    public function getIcon(): ?string
    {
        return match($this) {
            self::HADIR => 'heroicon-o-check-circle',
            self::TERLAMBAT => 'heroicon-o-exclamation-triangle',
            self::TIDAK_HADIR => 'heroicon-o-x-circle',
            self::IZIN => 'heroicon-o-information-circle',
            self::SAKIT => 'heroicon-o-information-circle'
        };
    }

    public static function labels(): array{
        
        return collect(self::cases())->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])->toArray();
    }

}
