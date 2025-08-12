<?php

namespace App\Services;

use App\Enums\AttendanceStatusEnum;
use App\Models\AttendanceRule;
use App\Models\Device;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates end-to-end attendance scan handling for students and teachers.
 *
 * Responsibilities:
 * - Determine effective attendance rule for scan time
 * - Create new attendance records or update existing ones (checkout)
 * - Send late notifications when applicable
 * - Broadcast scan events for real-time updates
 */
class AttendanceProcessingService
{
    /**
     * Service used to resolve attendance rules and status derivation.
     */
    protected AttendanceService $attendanceService;

    /**
     * Create a new service instance.
     *
     * @param AttendanceService $attendanceService
     */
    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Handle an attendance scan for a user (Student or Teacher).
     *
     * Creates a new attendance when none exists for the date; otherwise processes
     * checkout if scan time is after the configured checkout window start, or
     * returns an informative response if already checked in/out.
     *
     * Side effects:
     * - Writes to attendance tables
     * - May send late notification to admins
     * - Broadcasts \App\Events\UserScanned
     *
     * @param Student|Teacher $user The scanning user
     * @param Device $device Device used for scanning
     * @param Carbon $scanDateTime Scan timestamp
     * @return JsonResponse Structured JSON API response
     */
    public function handleScan(Student|Teacher $user, Device $device, Carbon $scanDateTime): JsonResponse
    {
        $classId = ($user instanceof Student) ? $user->class_id : null;
        $attendanceRule = $this->attendanceService->getAttendanceRule($classId, $scanDateTime);

        $attendance = $this->findExistingAttendance($user, $scanDateTime->toDateString());

        if (! $attendance) {
            return $this->createNewAttendance($user, $scanDateTime, $attendanceRule, $device);
        }

        return $this->handleExistingAttendance($user, $attendance, $scanDateTime, $attendanceRule);
    }

    /**
     * Look up an existing attendance record for the given user and date.
     *
     * @param Student|Teacher $user
     * @param string $scanDate Date in Y-m-d format
     * @return StudentAttendance|TeacherAttendance|null
     */
    private function findExistingAttendance(Student|Teacher $user, string $scanDate)
    {
        $model = $user instanceof Student ? StudentAttendance::class : TeacherAttendance::class;
        $foreignKey = $user instanceof Student ? 'student_id' : 'teacher_id';

        Log::debug('findExistingAttendance: Checking for existing attendance.', [
            'model' => $model,
            'foreign_key' => $foreignKey,
            'user_id' => $user->id,
            'scan_date' => $scanDate
        ]);

        $attendance = $model::where($foreignKey, $user->id)->where('date', $scanDate)->first();

        if ($attendance) {
            Log::debug('findExistingAttendance: Existing attendance found.', ['attendance_id' => $attendance->id]);
        } else {
            Log::debug('findExistingAttendance: No existing attendance found.');
        }

        return $attendance;
    }

    /**
     * Create a new attendance record for the scan and send late notification if needed.
     *
     * @param Student|Teacher $user
     * @param Carbon $scanDateTime
     * @param AttendanceRule $attendanceRule Effective rule for the day
     * @param Device $device
     * @return JsonResponse
     */
    private function createNewAttendance(Student|Teacher $user, Carbon $scanDateTime, AttendanceRule $attendanceRule, Device $device): JsonResponse
    {
        $model = $user instanceof Student ? new StudentAttendance : new TeacherAttendance;
        $foreignKey = $user instanceof Student ? 'student_id' : 'teacher_id';

        $model->{$foreignKey} = $user->id;
        $model->time_in = $scanDateTime->toTimeString();
        $model->date = $scanDateTime->toDateString();
        $model->device_id = $device->id;
        $status = $this->attendanceService->checkAttendanceStatus($scanDateTime, $attendanceRule);
        $model->status = $status;
        $model->save();

        if ($status === AttendanceStatusEnum::TERLAMBAT) {
            $this->sendLateNotification($user, $scanDateTime);
        }

        Log::info('New attendance record created', ['user_id' => $user->id, 'status' => $model->status->value]);
        broadcast(new \App\Events\UserScanned($user->fingerprint_id));

        return $this->buildSuccessResponse($user, $model, 'Scan masuk berhasil');
    }

    /**
     * Handle a scan for a user who already has an attendance record today.
     *
     * If time is before checkout window, returns an already-checked-in response.
     * If checkout time is not set yet and time is valid, processes checkout.
     * Otherwise indicates the day is already completed.
     *
     * @param Student|Teacher $user
     * @param StudentAttendance|TeacherAttendance $attendance
     * @param Carbon $scanDateTime
     * @param AttendanceRule $attendanceRule
     * @return JsonResponse
     */
    private function handleExistingAttendance(Student|Teacher $user, $attendance, Carbon $scanDateTime, AttendanceRule $attendanceRule): JsonResponse
    {
        // Bandingkan hanya bagian waktunya saja untuk menghindari masalah tanggal
        $scanTime = Carbon::parse($scanDateTime->toTimeString());
        $checkoutStartTime = Carbon::parse($attendanceRule->time_out_start);

        if ($scanTime->isBefore($checkoutStartTime)) {
            return $this->buildAlreadyCheckedInResponse($user, $attendance);
        }

        if (! $attendance->time_out) {
            return $this->processCheckout($user, $attendance, $scanDateTime);
        }

        return $this->buildAlreadyCompletedResponse($user, $attendance);
    }

    /**
     * Complete checkout for the given attendance by setting time_out.
     *
     * @param Student|Teacher $user
     * @param StudentAttendance|TeacherAttendance $attendance
     * @param Carbon $scanDateTime
     * @return JsonResponse
     */
    private function processCheckout(Student|Teacher $user, $attendance, Carbon $scanDateTime): JsonResponse
    {
        $attendance->time_out = $scanDateTime->toTimeString();
        $attendance->save();

        Log::info('Attendance record updated with time_out', ['user_id' => $user->id]);

        return response()->json([
            'status' => 'success',
            'message' => 'Scan keluar berhasil',
            'data' => $this->formatResponseData($user, $attendance),
        ]);
    }

    /**
     * Send a late notification to admin users for a tardy scan.
     *
     * @param Student|Teacher $user
     * @param Carbon $scanDateTime
     * @return void
     */
    private function sendLateNotification(Student|Teacher $user, Carbon $scanDateTime): void
    {
        $recipients = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        $userName = $user->name;
        // Time will now be formatted in Asia/Makassar timezone automatically
        $scanTime = $scanDateTime->format('H:i');

        $notification = Notification::make()
            ->title('Siswa Terlambat')
            ->body("{$userName} tercatat terlambat pada jam {$scanTime}.")
            ->warning();

        foreach ($recipients as $recipient) {
            $notification->sendToDatabase($recipient);
        }
    }

    /**
     * Build a successful JSON response payload.
     *
     * @param Student|Teacher $user
     * @param StudentAttendance|TeacherAttendance $attendance
     * @param string $message
     * @return JsonResponse
     */
    private function buildSuccessResponse(Student|Teacher $user, $attendance, string $message): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $this->formatResponseData($user, $attendance),
        ]);
    }

    /**
     * Build response indicating the user already checked in.
     *
     * @param Student|Teacher $user
     * @param StudentAttendance|TeacherAttendance $attendance
     * @return JsonResponse
     */
    private function buildAlreadyCheckedInResponse(Student|Teacher $user, $attendance): JsonResponse
    {
        Log::info('User already checked in', ['user_id' => $user->id]);

        return response()->json([
            'status' => 'info',
            'message' => 'Anda sudah melakukan absensi masuk',
            'data' => $this->formatResponseData($user, $attendance),
        ], 409);
    }

    /**
     * Build response indicating user has completed both check-in and check-out.
     *
     * @param Student|Teacher $user
     * @param StudentAttendance|TeacherAttendance $attendance
     * @return JsonResponse
     */
    private function buildAlreadyCompletedResponse(Student|Teacher $user, $attendance): JsonResponse
    {
        return response()->json([
            'status' => 'info',
            'message' => 'Anda sudah melakukan scan masuk dan keluar hari ini',
            'data' => $this->formatResponseData($user, $attendance),
        ], 409);
    }

    /**
     * Format common response data for attendance operations.
     *
     * Keys:
     * - student_name|teacher_name
     * - time_in (H:i:s|null)
     * - time_out (H:i:s|null)
     * - status (string)
     * - date (Y-m-d)
     *
     * @param Student|Teacher $user
     * @param StudentAttendance|TeacherAttendance $attendance
     * @return array<string, mixed>
     */
    private function formatResponseData(Student|Teacher $user, $attendance): array
    {
        $userNameKey = $user instanceof Student ? 'student_name' : 'teacher_name';

        $data = [
            $userNameKey => $user->name,
            'time_in' => $attendance->time_in ? $attendance->time_in->format('H:i:s') : null,
            'time_out' => $attendance->time_out ? $attendance->time_out->format('H:i:s') : null,
            'status' => $attendance->status->value,
            'date' => $attendance->date,
        ];

        return $data;
    }
}
