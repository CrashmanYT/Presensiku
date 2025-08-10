<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatusEnum;
use App\Events\StudentAttendanceUpdated;
use App\Models\Device;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SendAbsentNotificationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the command only sends notifications for students who are absent today.
     *
     * @return void
     */
    public function test_sends_notifications_only_for_students_absent_today(): void
    {
        // 1. Fake the Event dispatcher
        Event::fake();

        // 2. Setup the data
        Device::factory()->create();

        // Student A: Absent today (should be notified)
        $studentA = Student::factory()->create();
        StudentAttendance::factory()->create([
            'student_id' => $studentA->id,
            'date' => now()->toDateString(),
            'status' => AttendanceStatusEnum::TIDAK_HADIR,
        ]);

        // Student B: Present today (should NOT be notified)
        $studentB = Student::factory()->create();
        StudentAttendance::factory()->create([
            'student_id' => $studentB->id,
            'date' => now()->toDateString(),
            'status' => AttendanceStatusEnum::HADIR,
        ]);

        // Student C: Absent yesterday (should NOT be notified)
        $studentC = Student::factory()->create();
        StudentAttendance::factory()->create([
            'student_id' => $studentC->id,
            'date' => now()->subDay()->toDateString(),
            'status' => AttendanceStatusEnum::TIDAK_HADIR,
        ]);

        // 3. Execute the Artisan command
        $this->artisan('attendance:send-absent-notifications --force')
            ->expectsOutput('Starting to process absent students for notification...')
            ->expectsOutput('Found 1 absent students. Dispatching events...')
            ->expectsOutput('Finished processing absent students.')
            ->assertExitCode(0);

        // 4. Assert that the event was dispatched only for the correct student
        Event::assertDispatched(StudentAttendanceUpdated::class, function ($event) use ($studentA) {
            // Check that the event was dispatched for Student A's attendance record
            return $event->studentAttendance->student_id === $studentA->id;
        });

        // 5. Assert that the event was dispatched exactly once
        Event::assertDispatchedTimes(StudentAttendanceUpdated::class, 1);
    }

    /**
     * Test the command when no students are absent.
     *
     * @return void
     */
    public function test_command_handles_no_absent_students(): void
    {
        // 1. Fake the Event dispatcher
        Event::fake();

        // 2. Setup data (no absent students for today)
        Device::factory()->create();
        $student = Student::factory()->create();
        StudentAttendance::factory()->create([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'status' => AttendanceStatusEnum::HADIR,
        ]);

        // 3. Execute the command
        $this->artisan('attendance:send-absent-notifications --force')
            ->expectsOutput('No absent students found for today. Nothing to do.')
            ->assertExitCode(0);

        // 4. Assert that no event was dispatched
        Event::assertNotDispatched(StudentAttendanceUpdated::class);
    }
}
