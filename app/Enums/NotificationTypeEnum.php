<?php

namespace App\Enums;

enum NotificationTypeEnum: string
{
    case IZIN = 'izin';
    case TIDAK_HADIR = 'tidak_hadir';
    case KETERLAMBATAN = 'keterlambatan';
    case REKAP = 'rekap'; 
}
