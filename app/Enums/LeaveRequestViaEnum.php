<?php

namespace App\Enums;

enum LeaveRequestViaEnum: string
{
    case FORM_ONLINE = 'form_online';
    case MANUAL = 'manual';
}
