<?php

namespace App\Enums;

enum AttendanceStatusEnum: string
{
    case HADIR = 'hadir';
    case TERLAMBAT = 'terlambat';
    case TIDAK_HADIR = 'tidak_hadir';
    case IZIN = 'izin';
    case SAKIT = 'sakit';
}
