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
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveRequestService
{
    /**
     * @throws \Throwable
     */
    public function processFromWebhook(Student|Teacher $user, array $data): void
    {
        Log::info('Starting leave request process from webhook.', ['user' => $user->name]);
        
        // Validate required fields
        $this->validateWebhookData($data);
        
        try {
            DB::transaction(function () use ($user, $data) {
                $newStartDate = Carbon::parse($data['start_date']);
                $newEndDate = Carbon::parse($data['end_date']);

                if ($user instanceof Student) {
                    $leaveRequestModel = new StudentLeaveRequest;
                    $attendanceModelClass = StudentAttendance::class;
                    $foreignKey = 'student_id';
                } else {
                    $leaveRequestModel = new TeacherLeaveRequest;
                    $attendanceModelClass = TeacherAttendance::class;
                    $foreignKey = 'teacher_id';
                }

                $this->handleOverlaps($leaveRequestModel, $user->id, $newStartDate, $newEndDate);
                $this->createLeaveRequest($leaveRequestModel, $user->id, $data);
                $this->syncToAttendance(new $attendanceModelClass, $foreignKey, $user->id, $newStartDate, $newEndDate, $data['type']);
            });

            // Send notification only after the main transaction is successfully committed
            DB::afterCommit(function () use ($user, $data) {
                $this->sendLeaveRequestNotification($user, $data);
            });

            Log::info('Successfully processed leave request from webhook.');
        } catch (\Exception $e) {
            Log::error('Failed to process leave request from webhook: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'data' => $data,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    private function handleOverlaps(StudentLeaveRequest|TeacherLeaveRequest $model, int $userId, Carbon $newStartDate, Carbon $newEndDate)
    {
        $foreignKey = $model instanceof StudentLeaveRequest ? 'student_id' : 'teacher_id';

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
        $foreignKey = $model instanceof StudentLeaveRequest ? 'student_id' : 'teacher_id';

        $model::create([
            $foreignKey => $userId,
            'type' => strtolower($data['type']),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'],
            'attachment' => $data['attachment'] ?? null,
            'via' => LeaveRequestViaEnum::FORM_ONLINE->value,
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

    private function sendLeaveRequestNotification(Student|Teacher $user, array $data): void
    {
        Log::info('Attempting to send leave request notification.');
        
        // Get all admin users with roles preloaded to avoid N+1 queries
        $recipients = User::with('roles')->whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        // If no admin users found, get all users as fallback
        if ($recipients->isEmpty()) {
            $recipients = User::all();
            Log::warning('No admin users found, sending notifications to all users.');
        }

        Log::info('Found ' . $recipients->count() . ' recipients for notification.');

        $userName = $user->name;
        $userType = $user instanceof Student ? 'Siswa' : 'Guru';
        $userIdentifier = $user instanceof Student ? $user->nis : $user->nip;
        $leaveType = ucfirst(strtolower($data['type']));
        $startDate = Carbon::parse($data['start_date'])->format('d/m/Y');
        $endDate = Carbon::parse($data['end_date'])->format('d/m/Y');

        $title = '📝 Pengajuan Izin Baru dari Google Form';
        $body = "{$userType} {$userName} ({$userIdentifier}) mengajukan {$leaveType} dari {$startDate} sampai {$endDate}. Alasan: {$data['reason']}";
        
        $notificationData = [
            'user_type' => $userType,
            'user_name' => $userName,
            'user_identifier' => $userIdentifier,
            'leave_type' => $leaveType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $data['reason'],
            'source' => 'google_form_webhook'
        ];

        // Send both Filament notification and Laravel notification to each recipient
        foreach ($recipients as $recipient) {
            Log::info('Sending notification to: ' . $recipient->email);
            
            try {
                // Send Filament notification (for immediate display)
                Notification::make()
                    ->title($title)
                    ->body($body)
                    ->success()
                    ->icon('heroicon-o-document-text')
                    ->iconColor('success')
                    ->duration(10000) // 10 seconds
                    ->sendToDatabase($recipient);
                
                // Send Laravel notification (for database persistence)
                $recipient->notify(new \App\Notifications\LeaveRequestNotification($title, $body, $notificationData));
                    
                Log::info('Successfully sent both notifications to: ' . $recipient->email);
            } catch (\Exception $e) {
                Log::error('Failed to send notification to: ' . $recipient->email . '. Error: ' . $e->getMessage());
            }
        }
        Log::info('Finished sending leave request notifications.');
    }

    /**
     * Validate webhook data to prevent security issues and data corruption
     */
    private function validateWebhookData(array $data): void
    {
        $requiredFields = ['start_date', 'end_date', 'type', 'reason'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing or empty");
            }
        }
        
        // Validate date formats
        try {
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid date format: " . $e->getMessage());
        }
        
        // Validate date logic
        if ($startDate->gt($endDate)) {
            throw new \InvalidArgumentException("Start date cannot be after end date");
        }
        
        // Validate leave type
        $validTypes = ['izin', 'sakit'];
        if (!in_array(strtolower($data['type']), $validTypes)) {
            throw new \InvalidArgumentException("Invalid leave type. Must be one of: " . implode(', ', $validTypes));
        }
        
        // Validate reason length
        if (strlen($data['reason']) > 1000) {
            throw new \InvalidArgumentException("Reason cannot exceed 1000 characters");
        }
    }
}