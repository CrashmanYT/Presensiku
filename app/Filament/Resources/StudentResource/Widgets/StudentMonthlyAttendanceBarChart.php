<?php

namespace App\Filament\Resources\StudentResource\Widgets;

use App\Models\StudentAttendance;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class StudentMonthlyAttendanceBarChart extends ChartWidget
{
    protected static ?string $heading = 'Statistik Kehadiran Per Tahun';

    public ?\App\Models\Student $record = null;

    #[Reactive]
    public $selectedChartYear;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        if (! $this->record || ! $this->record->id) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $year = $this->selectedChartYear ?? now()->year;

        $monthlyData = StudentAttendance::query()
            ->select(
                DB::raw('MONTH(date) as month'),
                DB::raw('COUNT(CASE WHEN status = \'hadir\' THEN 1 END) as total_hadir'),
                DB::raw('COUNT(CASE WHEN status = \'terlambat\' THEN 1 END) as total_terlambat'),
                DB::raw('COUNT(CASE WHEN status = \'tidak_hadir\' THEN 1 END) as total_tidak_hadir'),
                DB::raw('COUNT(CASE WHEN status = \'sakit\' THEN 1 END) as total_sakit'),
                DB::raw('COUNT(CASE WHEN status = \'izin\' THEN 1 END) as total_izin')
            )
            ->where('student_id', $this->record->id)
            ->whereYear('date', $year)
            ->groupBy(DB::raw('MONTH(date)'))
            ->orderBy(DB::raw('MONTH(date)'))
            ->get()
            ->keyBy('month');

        $labels = [];
        $hadir = [];
        $terlambat = [];
        $tidakHadir = [];
        $sakit = [];
        $izin = [];

        for ($i = 1; $i <= 12; $i++) {
            $monthName = Carbon::create(null, $i, 1)->format('F');
            $labels[] = $monthName;

            $data = $monthlyData->get($i, (object) [
                'total_hadir' => 0,
                'total_terlambat' => 0,
                'total_tidak_hadir' => 0,
                'total_sakit' => 0,
                'total_izin' => 0,
            ]);

            $hadir[] = $data->total_hadir;
            $terlambat[] = $data->total_terlambat;
            $tidakHadir[] = $data->total_tidak_hadir;
            $sakit[] = $data->total_sakit;
            $izin[] = $data->total_izin;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Hadir',
                    'data' => $hadir,
                    'backgroundColor' => '#4CAF50',
                ],
                [
                    'label' => 'Terlambat',
                    'data' => $terlambat,
                    'backgroundColor' => '#FFC107',
                ],
                [
                    'label' => 'Alpa',
                    'data' => $tidakHadir,
                    'backgroundColor' => '#F44336',
                ],
                [
                    'label' => 'Sakit',
                    'data' => $sakit,
                    'backgroundColor' => '#03A9F4',
                ],
                [
                    'label' => 'Izin',
                    'data' => $izin,
                    'backgroundColor' => '#2196F3',
                ],
            ],
        ];
    }
}
