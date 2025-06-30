<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ScanResultEnum: string implements HasLabel, HasColor
{
    case SUCCESS = 'success';
    case FAIL = 'fail';

    public function getColor(): string|array|null
    {
        return match($this) {
            self::SUCCESS => Color::Green,
            self::FAIL => Color::Red,
        };
    }
    public function getLabel(): ?string
    {
        return match($this) {
            self::SUCCESS => 'Success',
            self::FAIL => 'Fail'
        };
    }
}
