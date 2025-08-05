<?php

namespace App\Exports;

use App\Models\StudentAttendance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RekapitulasiAbsensiExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected string $activeTab;
    protected string $startDate;
    protected string $endDate;
    protected ?int $classId = null;
 
    public function __construct(string $activeTab, string $startDate, string $endDate, ?int $classId = null)
    {
        $this->activeTab = $activeTab;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->classId = $classId;
    }

    public function query()
    {
        if ($this->activeTab === 'harian') {
            return StudentAttendance::query()
                ->with(['student.class', 'device'])
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->when($this->classId, function ($q) {
                    $q->whereHas('student', function ($qs) {
                        $qs->where('class_id', $this->classId);
                    });
                });
        }
 
        return StudentAttendance::query()
            ->select(
                'students.id as student_id',
                DB::raw('students.name as student_name'),
                DB::raw('classes.name as class_name'),
                DB::raw('COUNT(student_attendances.id) as total_hari_absensi'),
                DB::raw("COUNT(CASE WHEN student_attendances.status = 'hadir' THEN 1 END) as total_hadir"),
                DB::raw("COUNT(CASE WHEN student_attendances.status = 'tidak_hadir' THEN 1 END) as total_tidak_hadir"),
                DB::raw("COUNT(CASE WHEN student_attendances.status = 'terlambat' THEN 1 END) as total_terlambat"),
                DB::raw("COUNT(CASE WHEN student_attendances.status = 'sakit' THEN 1 END) as total_sakit"),
                DB::raw("COUNT(CASE WHEN student_attendances.status = 'izin' THEN 1 END) as total_izin")
            )
            ->join('students', 'student_attendances.student_id', '=', 'students.id')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->when($this->classId, function ($q) {
                $q->where('students.class_id', $this->classId);
            })
            ->whereBetween('student_attendances.date', [$this->startDate, $this->endDate])
            ->groupBy('students.id', 'students.name', 'classes.name')
            ->orderBy('students.name', 'asc');
    }

    public function headings(): array
    {
        if ($this->activeTab === 'harian') {
            return ['Nama Siswa', 'Kelas', 'Tanggal', 'Jam Masuk', 'Status'];
        }

        return ['Nama Siswa', 'Kelas', 'Hadir', 'Terlambat', 'Alpa', 'Sakit', 'Izin', 'Kehadiran Total (%)'];
    }

    public function map($row): array
    {
        if ($this->activeTab === 'harian') {
            return [
                optional($row->student)->name,
                optional(optional($row->student)->class)->name,
                $row->date,
                $row->time_in,
                method_exists($row->status, 'getLabel') ? $row->status->getLabel() : (string) $row->status,
            ];
        }

        $total_hari_absensi = $row->total_hari_absensi ?? 0;
        $persentase_kehadiran = 0;
        if ($total_hari_absensi > 0) {
            $total_hadir_tercatat = $row->total_hadir + $row->total_terlambat + $row->total_sakit + $row->total_izin;
            $persentase_kehadiran = round(($total_hadir_tercatat / $total_hari_absensi) * 100, 2);
        }

        return [
            $row->student_name,
            $row->class_name,
            $row->total_hadir,
            $row->total_terlambat,
            $row->total_tidak_hadir,
            $row->total_sakit,
            $row->total_izin,
            $persentase_kehadiran . '%',
        ];
    }
}