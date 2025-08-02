<?php

namespace App\Services;

use App\Enums\LeaveRequestViaEnum;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentLeaveRequest;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Models\TeacherLeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveRequestService
{
    public function processFromWebhook(Student|Teacher $user, array $data)
    {
        DB::beginTransaction();
        try {
            $newStartDate = Carbon::parse($data['start_date']);
            $newEndDate = Carbon::parse($data['end_date']);

            if ($user instanceof Student) {
                $leaveRequestModel = new StudentLeaveRequest();
                $attendanceModelClass = StudentAttendance::class;
                $foreignKey = 'student_id';
            } else {
                $leaveRequestModel = new TeacherLeaveRequest();
                $attendanceModelClass = TeacherAttendance::class;
                $foreignKey = 'teacher_id';
            }

            $this->handleOverlaps($leaveRequestModel, $user->id, $newStartDate, $newEndDate);
            $this->createLeaveRequest($leaveRequestModel, $user->id, $data);
            $this->syncToAttendance(new $attendanceModelClass() , $foreignKey, $user->id, $newStartDate, $newEndDate, $data['type']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

    }

    private function handleOverlaps(StudentLeaveRequest | TeacherLeaveRequest $model, int $userId, Carbon $newStartDate, Carbon $newEndDate) {
        $foreignKey = $model instanceof StudentLeaveRequest ? "student_id" : "teacher_id";

        $overlapping = $model::where($foreignKey, $userId)
            ->where('start_date', '<=', $newEndDate)
            ->where('end_date', '>=', $newStartDate)
            ->get();

        foreach ($overlapping as $oldRequest) {
            $oldStartDate = Carbon::parse($oldRequest->start_date);
            $oldEndDate = Carbon::parse($oldRequest->end_date);

            // Kasus 1: Permintaan baru menelan sepenuhnya permintaan lama -> Hapus yang lama
            if ($newStartDate->lte($oldStartDate) && $newEndDate->gte($oldEndDate)) {
                $oldRequest->delete();
                continue;
            }
            // Kasus 2: Permintaan baru memotong bagian akhir dari yang lam
            if ($newStartDate->gt($oldStartDate) && $newStartDate->lte($oldEndDate)) {
                $oldRequest->end_date = $newStartDate->copy()->subDay()->toDateString();
            }

            if ($newEndDate->gte($oldStartDate) && $newEndDate->lt($oldEndDate)) {
                $oldRequest->start_date = $newEndDate->copy()->addDay()->toDateString();
            }
            $oldRequest->save();
        }
    }

    private function createLeaveRequest(StudentLeaveRequest|TeacherLeaveRequest $model, int $userId, array $data)
    {
        $foreignKey = $model instanceof StudentLeaveRequest ? "student_id" : "teacher_id";

        $model::create([
            $foreignKey => $userId,
            'type' => strtolower($data['type']),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'],
            'attachment_url' => $data['attachment_url'] ?? null,
            'via' => LeaveRequestViaEnum::FORM_ONLINE->value
        ]);
    }

    private function syncToAttendance(StudentAttendance | TeacherAttendance $attendanceModelClass, string $foreignKey, int $userId, Carbon $startDate, Carbon $endDate, string $type)
    {
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $attendanceModelClass::updateOrCreate(
                [
                    $foreignKey => $userId,
                    'date' => $currentDate->toDateString(),
                ],
                [
                    'status' => strtolower($type),
                    'time_in' => null,
                    'time_out' => null,
                    'photo_in' => null,
                ]
            );
            $currentDate->addDay();
        }
    }
}
