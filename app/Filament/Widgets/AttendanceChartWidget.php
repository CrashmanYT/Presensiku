<?php

namespace App\Filament\Widgets;

use App\Models\StudentAttendance;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class AttendanceChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Statistik Kehadiran Siswa';

    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public ?string $filter = 'last_7_days'; // Default filter value

    protected function getFilters(): ?array
    {
        return [
            'last_7_days' => '7 Hari Terakhir',
            'last_30_days' => '30 Hari Terakhir',
            'this_month' => 'Bulan Ini',
            'last_month' => 'Bulan Lalu',
            'last_3_months' => '3 Bulan Terakhir',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $startDate = null;
        $endDate = Carbon::today();

        switch ($this->filter) {
            case 'last_7_days':
                $startDate = Carbon::today()->subDays(6);
                break;
            case 'last_30_days':
                $startDate = Carbon::today()->subDays(29);
                break;
            case 'this_month':
                $startDate = Carbon::today()->startOfMonth();
                break;
            case 'last_month':
                $startDate = Carbon::today()->subMonth()->startOfMonth();
                $endDate = Carbon::today()->subMonth()->endOfMonth();
                break;
            case 'last_3_months':
                $startDate = Carbon::today()->subMonths(2)->startOfMonth();
                break;
            default:
                $startDate = Carbon::today()->subDays(6);
                break;
        }

        $data = [];
        $labels = [];

        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $labels[] = $currentDate->format('M d');

            $hadir = StudentAttendance::whereDate('date', $currentDate)->where('status', 'hadir')->count();
            $terlambat = StudentAttendance::whereDate('date', $currentDate)->where('status', 'terlambat')->count();
            $tidakHadir = StudentAttendance::whereDate('date', $currentDate)->where('status', 'tidak_hadir')->count();
            $izin = StudentAttendance::whereDate('date', $currentDate)->where('status', 'izin')->count();

            $data['hadir'][] = $hadir;
            $data['terlambat'][] = $terlambat;
            $data['tidak_hadir'][] = $tidakHadir;
            $data['izin'][] = $izin;

            $currentDate->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Hadir',
                    'data' => $data['hadir'],
                    'borderColor' => '#36A2EB',
                    'backgroundColor' => '#9BD0F5',
                ],
                [
                    'label' => 'Terlambat',
                    'data' => $data['terlambat'],
                    'borderColor' => '#FF6384',
                    'backgroundColor' => '#FFB1C1',
                ],
                [
                    'label' => 'Tidak Hadir',
                    'data' => $data['tidak_hadir'],
                    'borderColor' => '#4BC0C0',
                    'backgroundColor' => '#C9FFCE',
                ],
                [
                    'label' => 'Izin',
                    'data' => $data['izin'],
                    'borderColor' => '#FFCD56',
                    'backgroundColor' => '#FFE699',
                ],
            ],
            'labels' => $labels,
        ];
    }
}