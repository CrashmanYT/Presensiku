<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NotificationRecipientEnum: string implements HasLabel, HasColor
{
    case ORANG_TUA = 'orang_tua';
    case WALI_KELAS = 'wali_kelas';
    case KESISWAAN = 'kesiswaan';

    public function getLabel(): ?string
    {
        return match($this) {
            self::ORANG_TUA => 'Orang Tua',
            self::WALI_KELAS => 'Wali Kelas',
            self::KESISWAAN => 'Kesiswaan',
        };
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::ORANG_TUA => Color::Green,
            self::WALI_KELAS => Color::Blue,
            self::KESISWAAN => Color::Yellow,
        };
    }
}
