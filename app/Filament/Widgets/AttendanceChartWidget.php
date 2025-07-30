<?php

namespace App\Filament\Widgets;

use App\Models\StudentAttendance;
use Carbon\CarbonPeriod;
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
        // 1. Tentukan Rentang Tanggal
        $endDate = Carbon::today();
        $startDate = match ($this->filter) {
            'last_30_days' => Carbon::today()->subDays(29),
            'this_month'   => Carbon::today()->startOfMonth(),
            'last_month'   => Carbon::today()->subMonthNoOverflow()->startOfMonth(),
            'last_3_months'=> Carbon::today()->subMonthsNoOverflow(2)->startOfMonth(),
            default        => Carbon::today()->subDays(6),
        };

        if ($this->filter === 'last_month') {
            $endDate = Carbon::today()->subMonthNoOverflow()->endOfMonth();
        }

        // 2. Siapkan Struktur Data Awal (Label dan Data Kosong) dengan loop 'while'
        $labels = [];
        $templateData = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->format('M d');
            $templateData[$dateString] = 0;
            $currentDate->addDay();
        }

        $data = [
            'hadir' => $templateData,
            'terlambat' => $templateData,
            'tidak_hadir' => $templateData,
            'izin' => $templateData,
            'sakit' => $templateData,
        ];

        // 3. Query Database
        $attendanceCounts = StudentAttendance::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('DATE(date) as date_only, status, COUNT(*) as count')
            ->groupBy('date_only', 'status')
            ->get();

        // 4. Isi Struktur Data dengan Hasil Query
        foreach ($attendanceCounts as $count) {
            if (isset($data[$count->status->value])) {
                $data[$count->status->value][$count->date_only] = $count->count;
            }
        }

        // 5. Finalisasi dan Return Data untuk Chart
        return [
            'datasets' => [
                [
                    'label' => 'Hadir',
                    'data' => array_values($data['hadir']),
                    'borderColor' => '#36A2EB',
                ],
                [
                    'label' => 'Terlambat',
                    'data' => array_values($data['terlambat']),
                    'borderColor' => '#FF6384',
                ],
                [
                    'label' => 'Tidak Hadir',
                    'data' => array_values($data['tidak_hadir']),
                    'borderColor' => '#FFC107',
                ],
                [
                    'label' => 'Izin',
                    'data' => array_values($data['izin']),
                    'borderColor' => '#4BC0C0',
                ],
                [
                    'label' => 'Sakit',
                    'data' => array_values($data['sakit']),
                    'borderColor' => '#9966FF',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
