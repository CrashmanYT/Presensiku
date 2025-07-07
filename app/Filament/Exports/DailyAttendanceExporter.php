<?php

namespace App\Filament\Exports;

use App\Models\StudentAttendance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class DailyAttendanceExporter implements FromQuery, WithHeadings, WithMapping
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query->with(['student.class', 'device']);
    }

    public function headings(): array
    {
        return [
            'Nama Siswa',
            'Kelas',
            'Tanggal',
            'Jam Masuk',
            'Jam Keluar',
            'Status',
            'Perangkat',
            'Dibuat Pada',
            'Diperbarui Pada',
        ];
    }

    public function map($attendance): array
    {
        return [
            $attendance->student->name ?? '-',
            $attendance->student->class->name ?? '-',
            $attendance->date,
            $attendance->time_in,
            $attendance->time_out,
            $attendance->status,
            $attendance->device->name ?? '-',
            $attendance->created_at,
            $attendance->updated_at,
        ];
    }
}
