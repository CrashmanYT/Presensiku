<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NotificationStatusEnum: string implements HasColor, HasLabel
{
    case SENT = 'sent';
    case FAILED = 'failed';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SENT => Color::Green,
            self::FAILED => Color::Red,
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SENT => 'Sent',
            self::FAILED => 'Failed',
        };
    }
}
