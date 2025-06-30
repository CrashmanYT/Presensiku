<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EventTypeEnum: string implements HasLabel
{
    case SCAN_IN = 'scan_in';
    case SCAN_OUT = 'scan_OUT';

    public function getLabel(): ?string
    {
        return match($this) {
            self::SCAN_IN => 'Scan In',
            self::SCAN_OUT => 'Scan Out',
        };
    }
}
