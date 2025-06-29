<?php

namespace App\Enums;

enum NotificationRecipientEnum: string
{
    case ORANG_TUA = 'orang_tua';
    case WALI_KELAS = 'wali_kelas';
    case KESISWAAN = 'kesiswaan';
}
