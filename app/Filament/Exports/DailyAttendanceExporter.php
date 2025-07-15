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
        return $this->query
            ->join('students', 'student_attendances.student_id', '=', 'students.id')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->leftJoin('devices', 'student_attendances.device_id', '=', 'devices.id')
            ->select(
                'students.name as student_name',
                'classes.name as class_name',
                'student_attendances.date',
                'student_attendances.time_in',
                'student_attendances.time_out',
                'student_attendances.status',
                'devices.name as device_name',
                'student_attendances.created_at',
                'student_attendances.updated_at'
            );
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

    public function map($row): array
    {
        return [
            $row->student_name,
            $row->class_name,
            $row->date,
            $row->time_in,
            $row->time_out,
            $row->status,
            $row->device_name,
            $row->created_at,
            $row->updated_at,
        ];
    }
}
