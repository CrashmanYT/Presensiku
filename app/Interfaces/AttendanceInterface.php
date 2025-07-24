<?php

namespace App\Interfaces;

use App\Models\AttendanceRule;
use Carbon\Carbon;

interface AttendanceInterface
{
    public function detectScanType(Carbon $scanTime, AttendanceRule $attendanceRule);
}
