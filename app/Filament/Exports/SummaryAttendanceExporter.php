<?php

namespace App\Filament\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class SummaryAttendanceExporter implements FromQuery, WithHeadings, WithMapping
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Nama Siswa',
            'Kelas',
            'Total Hadir',
            'Total Terlambat',
            'Total Alpa',
            'Total Sakit',
            'Total Izin',
        ];
    }

    public function map($summary): array
    {
        return [
            $summary->student_name,
            $summary->class_name,
            $summary->total_hadir,
            $summary->total_terlambat,
            $summary->total_tidak_hadir,
            $summary->total_sakit,
            $summary->total_izin,
        ];
    }
}
