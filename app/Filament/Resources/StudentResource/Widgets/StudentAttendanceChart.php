<?php

namespace App\Filament\Resources\StudentResource\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Livewire\Attributes\Reactive;


class StudentAttendanceChart extends ChartWidget
{
    protected static ?string $heading = 'Ringkasan Kehadiran Bulanan';

    public ?\App\Models\Student $record = null;
    #[Reactive]
    public $selectedMonth;
    #[Reactive]
    public $selectedYear;

    protected int | string | array $columnSpan = 1;
    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->endOfMonth();

        $attendances = \App\Models\StudentAttendance::query()
            ->where('student_id', $this->record->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $totalHadir = $attendances->where('status', 'hadir')->count();
        $totalTerlambat = $attendances->where('status', 'terlambat')->count();
        $totalTidakHadir = $attendances->where('status', 'tidak_hadir')->count();
        $totalSakit = $attendances->where('status', 'sakit')->count();
        $totalIzin = $attendances->where('status', 'izin')->count();

        return [
            'labels' => [
                'Hadir',
                'Terlambat',
                'Alpa',
                'Sakit',
                'Izin',
            ],
            'datasets' => [
                [
                    'data' => [
                        $totalHadir,
                        $totalTerlambat,
                        $totalTidakHadir,
                        $totalSakit,
                        $totalIzin,
                    ],
                    'backgroundColor' => [
                        '#4CAF50', // Hadir (Green)
                        '#FFC107', // Terlambat (Yellow)
                        '#F44336', // Alpa (Red)
                        '#03A9F4', // Sakit (Light Blue)
                        '#2196F3', // Izin (Blue)
                    ],
                ],
            ],
        ];
    }
}

