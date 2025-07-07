<?php

namespace App\Filament\Resources\StudentAttendanceResource\Widgets;

use App\Models\StudentAttendance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentAttendanceStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [];
    }
}
