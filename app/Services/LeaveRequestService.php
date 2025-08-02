<?php

namespace App\Services;

use App\Enums\LeaveRequestViaEnum;
use App\Models\Holiday;
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
    /**
     * @throws \Throwable
     */
    public function processFromWebhook(Student|Teacher $user, array $data): void
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

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function handleOverlaps(StudentLeaveRequest|TeacherLeaveRequest $model, int $userId, Carbon $newStartDate, Carbon $newEndDate)
    {
        $foreignKey = $model instanceof StudentLeaveRequest ? "student_id" : "teacher_id";

        $overlappingRequests = $model::where($foreignKey, $userId)
            ->where('start_date', '<=', $newEndDate)
            ->where('end_date', '>=', $newStartDate)
            ->get();

        foreach ($overlappingRequests as $oldRequest) {
            $oldStartDate = Carbon::parse($oldRequest->start_date);
            $oldEndDate = Carbon::parse($oldRequest->end_date);

            // Scenario 1: New request is in the middle of an old one (requires a split).
            if ($newStartDate->gt($oldStartDate) && $newEndDate->lt($oldEndDate)) {
                $originalOldEndDate = $oldRequest->end_date;

                // Part 1: Trim the old request to become the first part.
                $oldRequest->end_date = $newStartDate->copy()->subDay()->toDateString();
                $oldRequest->save();

                // Part 2: Create a new record for the remainder of the old request.
                $model::create([
                    $foreignKey => $userId,
                    'type' => $oldRequest->type,
                    'start_date' => $newEndDate->copy()->addDay()->toDateString(),
                    'end_date' => $originalOldEndDate,
                    'reason' => $oldRequest->reason,
                    'attachment' => $oldRequest->attachment,
                    'via' => $oldRequest->via,
                ]);

            // Scenario 2: New request completely swallows the old one.
            } elseif ($newStartDate->lte($oldStartDate) && $newEndDate->gte($oldEndDate)) {
                $oldRequest->delete();

            // Scenario 3: New request trims the end of the old one.
            } elseif ($newStartDate->gt($oldStartDate) && $newStartDate->lte($oldEndDate)) {
                $oldRequest->end_date = $newStartDate->copy()->subDay()->toDateString();
                $oldRequest->save();

            // Scenario 4: New request trims the start of the old one.
            } elseif ($newEndDate->lt($oldEndDate) && $newEndDate->gte($oldStartDate)) {
                $oldRequest->start_date = $newEndDate->copy()->addDay()->toDateString();
                $oldRequest->save();
            }
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
            'attachment' => $data['attachment'] ?? null,
            'via' => LeaveRequestViaEnum::FORM_ONLINE->value
        ]);
    }

    private function syncToAttendance(StudentAttendance|TeacherAttendance $attendanceModelClass, string $foreignKey, int $userId, Carbon $startDate, Carbon $endDate, string $type)
    {
        $holidays = Holiday::where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
            })->get();

        $holidayList = [];
        foreach ($holidays as $holiday) {
            $period = Carbon::parse($holiday->start_date)->toPeriod($holiday->end_date);
            foreach ($period as $date) {
                $holidayList[$date->format('Y-m-d')] = true;
            }
        }

        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            if ($currentDate->isWeekend() || isset($holidayList[$currentDate->toDateString()])) {
                $currentDate->addDay();
                continue;
            }

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