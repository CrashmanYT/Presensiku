<?php

namespace App\Enums;

enum NotificationStatusEnum: string
{
    case SENT = 'sent';
    case FAILED = 'failed';
}
