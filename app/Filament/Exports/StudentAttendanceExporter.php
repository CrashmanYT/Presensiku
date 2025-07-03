<?php

namespace App\Filament\Exports;

use App\Models\StudentAttendance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentAttendanceExporter implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(protected \Illuminate\Database\Eloquent\Builder $query)
    {
    }

    public function query()
    {
        // Ambil query yang sudah difilter dari Filament
        // dan pastikan hanya memilih kolom yang dibutuhkan dari semua tabel terkait.
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
                'devices.name as device_name'
            );
    }

    public function headings(): array
    {
        return [
            'Nama',
            'Kelas',
            'Tanggal',
            'Jam Masuk',
            'Jam Keluar',
            'Status',
            'Nama Device',
        ];
    }

    public function map($row): array
    {
        // Karena kita sudah memilih data yang tepat di query,
        // $row sekarang adalah objek standar yang ringan, bukan Model Eloquent.
        // Kita hanya perlu mengembalikan nilainya sebagai array.
        return [
            $row->student_name,
            $row->class_name,
            $row->date,
            $row->time_in,
            $row->time_out,
            $row->status,
            $row->device_name,
        ];
    }
}
