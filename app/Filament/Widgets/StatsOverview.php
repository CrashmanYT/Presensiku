<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        $totalStudents = Student::count();
        $presentStudents = StudentAttendance::whereDate('date', $today)->where('status', 'hadir')->count();
        $lateStudents = StudentAttendance::whereDate('date', $today)->where('status', 'terlambat')->count();
        $absentStudents = StudentAttendance::whereDate('date', $today)->where('status', 'tidak_hadir')->count();
        $leaveStudents = StudentAttendance::whereDate('date', $today)->where('status', 'izin')->count();

        $presentTrend = [];
        $lateTrend = [];
        $absentTrend = [];
        $leaveTrend = [];
        $totalStudentTrend = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);

            $presentTrend[] = StudentAttendance::whereDate('date', $date)->where('status', 'hadir')->count();
            $lateTrend[] = StudentAttendance::whereDate('date', $date)->where('status', 'terlambat')->count();
            $absentTrend[] = StudentAttendance::whereDate('date', $date)->where('status', 'tidak_hadir')->count();
            $leaveTrend[] = StudentAttendance::whereDate('date', $date)->where('status', 'izin')->count();
            $totalStudentTrend[] = $totalStudents; // Total siswa cenderung stabil, jadi trennya datar
        }

        return [
            Stat::make('Total Siswa', $totalStudents)
                ->description('Jumlah keseluruhan siswa')
                ->color('info')
                ->chart($totalStudentTrend),
            Stat::make('Hadir Hari Ini', $presentStudents)
                ->description('Siswa yang hadir hari ini')
                ->color('success')
                ->chart($presentTrend),
            Stat::make('Terlambat Hari Ini', $lateStudents)
                ->description('Siswa yang terlambat hari ini')
                ->color('warning')
                ->chart($lateTrend),
            Stat::make('Tidak Hadir Hari Ini', $absentStudents)
                ->description('Siswa yang tidak hadir hari ini')
                ->color('danger')
                ->chart($absentTrend),
            Stat::make('Izin Hari Ini', $leaveStudents)
                ->description('Siswa yang izin hari ini')
                ->color('primary')
                ->chart($leaveTrend),
        ];
    }
}
