<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AttendanceChartWidget;
use App\Filament\Widgets\RecentScansWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\StudentMonthlyAttendanceChart;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = 'Laporan & Analitik';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Dasbor';

    protected static string $view = 'filament.pages.dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            AttendanceChartWidget::class,
            StudentMonthlyAttendanceChart::class,
            RecentScansWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|string|array
    {
        return 2;
    }
}
