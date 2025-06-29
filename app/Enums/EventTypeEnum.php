<?php

namespace App\Enums;

enum EventTypeEnum: int
{
    case SCAN_IN = 'scan_in';
    case SCAN_OUT = 'scan_OUT';
}
