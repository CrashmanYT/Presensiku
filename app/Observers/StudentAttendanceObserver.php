<?php

namespace App\Observers;

use App\Enums\AttendanceStatusEnum;
use App\Events\StudentAttendanceUpdated;
use App\Contracts\SettingsRepositoryInterface;
use App\Models\DisciplineRanking;
use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StudentAttendanceObserver
{
    public function __construct(private SettingsRepositoryInterface $settings)
    {
    }
    // Define the statuses that should trigger a WhatsApp notification
    private const WHATSAPP_NOTIFY_STATUSES = [
        'izin',
        'terlambat',
        'sakit',
    ];

    /**
     * Handle the StudentAttendance "created" event.
     */
    public function created(StudentAttendance $studentAttendance): void
    {
        try {
            if (in_array($studentAttendance->status->value, self::WHATSAPP_NOTIFY_STATUSES)) {
                StudentAttendanceUpdated::dispatch($studentAttendance);
            }

            if ($studentAttendance->student) {
                $this->updateRanking($studentAttendance->student, $studentAttendance->date, $studentAttendance->status->value, 'increment');
            }

        } catch (\Exception $e) {
            Log::error('Error in StudentAttendanceObserver created method: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * Handle the StudentAttendance "updated" event.
     */
    public function updated(StudentAttendance $studentAttendance): void
    {
        try {
            if ($studentAttendance->isDirty('status') && $studentAttendance->student) {
                $originalStatus = $studentAttendance->getOriginal('status');
                $newStatus = $studentAttendance->status;
                $oldStatusValue = $originalStatus instanceof \App\Enums\AttendanceStatusEnum
                    ? $originalStatus->value
                    : $originalStatus;

                $newStatusValue = $newStatus->value;

                if ($oldStatusValue) {
                    $this->updateRanking($studentAttendance->student, $studentAttendance->date, $oldStatusValue, 'decrement');
                }

                $this->updateRanking($studentAttendance->student, $studentAttendance->date, $newStatusValue, 'increment');
            }
        } catch (\Exception $e) {
            Log::error('Error in StudentAttendanceObserver updated method: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * Handle the StudentAttendance "deleted" event.
     */
    public function deleted(StudentAttendance $studentAttendance): void
    {
        try {
            if ($studentAttendance->student) {
                $this->updateRanking($studentAttendance->student, $studentAttendance->date, $studentAttendance->status->value, 'decrement');
            }
        } catch (\Exception $e) {
            Log::error('Error in StudentAttendanceObserver deleted method: ' . $e->getMessage(), ['exception' => $e]);
        }
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
     * @param  Student  $student  Siswa yang terlibat.
     * @param  string  $date  Tanggal absensi untuk menentukan bulan.
     * @param  string  $status  Status kehadiran (hadir, terlambat, dll.).
     * @param  string  $operation  Tipe operasi: 'increment' atau 'decrement'.
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
        if (! $columnToUpdate) {
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
     * @param  DisciplineRanking  $ranking  Data peringkat yang akan dihitung ulang.
     */
    private function recalculateTotalScore(DisciplineRanking $ranking): void
    {
        $scores = [
            'hadir' => (int) $this->settings->get('discipline.scores.hadir', 5),
            'terlambat' => (int) $this->settings->get('discipline.scores.terlambat', -2),
            'izin' => (int) $this->settings->get('discipline.scores.izin', 0),
            'sakit' => (int) $this->settings->get('discipline.scores.sakit', 0),
            'tidak_hadir' => (int) $this->settings->get('discipline.scores.tidak_hadir', -5),
        ];

        $totalScore =
            ($ranking->total_present * $scores['hadir']) +
            ($ranking->total_late * $scores['terlambat']) +
            ($ranking->total_absent * $scores['tidak_hadir']);
        // Skor untuk izin dan sakit adalah 0, jadi tidak perlu dihitung

        $ranking->update(['score' => $totalScore]);
        $ranking->save();
    }
}
