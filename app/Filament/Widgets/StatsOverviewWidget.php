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
        $sevenDaysAgo = Carbon::today()->subDays(6);

        // Single query to get total students
        $totalStudents = Student::count();

        // Single query to get today's attendance by status
        $todayAttendance = StudentAttendance::whereDate('date', $today)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $presentStudents = $todayAttendance['hadir'] ?? 0;
        $lateStudents = $todayAttendance['terlambat'] ?? 0;
        $absentStudents = $todayAttendance['tidak_hadir'] ?? 0;
        $leaveStudents = $todayAttendance['izin'] ?? 0;

        // Single query to get 7 days trend data
        $trendData = StudentAttendance::whereBetween('date', [$sevenDaysAgo, $today])
            ->selectRaw('date, status, count(*) as count')
            ->groupBy('date', 'status')
            ->get()
            ->groupBy('date');

        $presentTrend = [];
        $lateTrend = [];
        $absentTrend = [];
        $leaveTrend = [];
        $totalStudentTrend = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');
            $dayData = $trendData->get($date, collect());

            $presentTrend[] = $dayData->where('status', 'hadir')->first()->count ?? 0;
            $lateTrend[] = $dayData->where('status', 'terlambat')->first()->count ?? 0;
            $absentTrend[] = $dayData->where('status', 'tidak_hadir')->first()->count ?? 0;
            $leaveTrend[] = $dayData->where('status', 'izin')->first()->count ?? 0;
            $totalStudentTrend[] = $totalStudents;
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
