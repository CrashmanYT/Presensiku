<?php

namespace App\Observers;

use App\Models\StudentAttendance;
use App\Models\DisciplineRanking;
use App\Helpers\SettingsHelper;
use App\Models\Student;
use Carbon\Carbon;

class StudentAttendanceObserver
{
    /**
     * Handle the StudentAttendance "created" event.
     */
    public function created(StudentAttendance $studentAttendance): void
    {
        $this->updateRanking($studentAttendance->student, $studentAttendance->date, $studentAttendance->status->value, 'increment');
    }

    /**
     * Handle the StudentAttendance "updated" event.
     */
    public function updated(StudentAttendance $studentAttendance): void
    {
        if ($studentAttendance->isDirty('status')) {
            $originalStatus = $studentAttendance->getOriginal('status');
            $newStatus = $studentAttendance->status; // Ini adalah objek Enum

            // SOLUSI: Pastikan status lama adalah string
            $oldStatusValue = $originalStatus instanceof \App\Enums\AttendanceStatusEnum
                ? $originalStatus->value
                : $originalStatus;

            // Pastikan status baru adalah string
            $newStatusValue = $newStatus->value;

            // Batalkan (decrement) skor untuk status lama
            if ($oldStatusValue) {
                $this->updateRanking($studentAttendance->student, $studentAttendance->date, $oldStatusValue, 'decrement');
            }
            
            // Tambahkan (increment) skor untuk status baru
            $this->updateRanking($studentAttendance->student, $studentAttendance->date, $newStatusValue, 'increment');
        }
    }

    /**
     * Handle the StudentAttendance "deleted" event.
     */
    public function deleted(StudentAttendance $studentAttendance): void
    {
        $this->updateRanking($studentAttendance->student, $studentAttendance->date, $studentAttendance->status->value, 'decrement');
    }

    /**
     * Handle the StudentAttendance "restored" event.
     */
    public function restored(StudentAttendance $studentAttendance): void
    {
        //
    }

    /**
     * Handle the StudentAttendance "force deleted" event.
     */
    public function forceDeleted(StudentAttendance $studentAttendance): void
    {
        //
    }

    /**
     * Method utama untuk memperbarui data peringkat disiplin.
     *
     * @param Student $student Siswa yang terlibat.
     * @param string $date Tanggal absensi untuk menentukan bulan.
     * @param string $status Status kehadiran (hadir, terlambat, dll.).
     * @param string $operation Tipe operasi: 'increment' atau 'decrement'.
     */
    private function updateRanking(Student $student, string $date, string $status, string $operation): void
    {
        // Tentukan bulan dari tanggal absensi, formatnya YYYY-MM
        $month = Carbon::parse($date)->format('Y-m');

        // Cari atau buat data peringkat untuk siswa di bulan yang sesuai
        $ranking = DisciplineRanking::firstOrCreate(
            [
                'student_id' => $student->id,
                'month' => $month,
            ],
            [
                'total_present' => 0,
                'total_late' => 0,
                'total_absent' => 0,
                'score' => 0,
            ]
        );

        // Tentukan kolom mana yang akan diubah berdasarkan status
        $columnToUpdate = match ($status) {
            'hadir' => 'total_present',
            'terlambat' => 'total_late',
            'tidak_hadir' => 'total_absent',
            default => null, // Untuk 'izin' dan 'sakit', kita tidak mengubah total
        };

        // Jika statusnya adalah izin atau sakit, tidak ada yang perlu diubah
        if (!$columnToUpdate) {
            return;
        }

        // Lakukan increment atau decrement pada kolom yang sesuai
        if ($operation === 'increment') {
            $ranking->increment($columnToUpdate);
        } else {
            $ranking->decrement($columnToUpdate);
        }

        // Hitung ulang skor total setelah pembaruan
        $this->recalculateTotalScore($ranking->fresh());
    }

    /**
     * Menghitung ulang skor total berdasarkan data terbaru.
     *
     * @param DisciplineRanking $ranking Data peringkat yang akan dihitung ulang.
     */
    private function recalculateTotalScore(DisciplineRanking $ranking): void
    {
        $scores = SettingsHelper::getDisciplineScores();

        $totalScore =
            ($ranking->total_present * $scores['hadir']) +
            ($ranking->total_late * $scores['terlambat']) +
            ($ranking->total_absent * $scores['tidak_hadir']);
            // Skor untuk izin dan sakit adalah 0, jadi tidak perlu dihitung

        $ranking->update(['score' => $totalScore]);
        $ranking->save();
    }
}
