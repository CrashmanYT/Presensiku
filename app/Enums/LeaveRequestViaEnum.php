<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LeaveRequestViaEnum: string implements HasColor, HasLabel
{
    case FORM_ONLINE = 'form_online';
    case MANUAL = 'manual';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FORM_ONLINE => 'Form Online',
            self::MANUAL => 'Manual'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::FORM_ONLINE => Color::Blue,
            self::MANUAL => Color::Emerald
        };
    }
}
