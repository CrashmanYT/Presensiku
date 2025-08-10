<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatusEnum;
use App\Models\Device;
use App\Models\Holiday;
use App\Models\Student;
use App\Models\StudentAttendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkStudentsAsAbsentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure we have a device for factories that need it.
        Device::factory()->create();
    }

    /**
     * Test the command correctly marks an absent student on a normal school day.
     */
    public function test_marks_absent_student_on_school_day(): void
    {
        // Ensure today is a weekday and not a holiday for this test.
        Carbon::setTestNow(Carbon::parse('next monday'));

        // 1. Setup the data
        $presentStudent = Student::factory()->create();
        $absentStudent = Student::factory()->create();

        // Create a record for the present student
        StudentAttendance::factory()->create([
            'student_id' => $presentStudent->id,
            'date' => now()->toDateString(),
            'status' => AttendanceStatusEnum::HADIR,
        ]);

        // 2. Execute the command
        $this->artisan('attendance:mark-absent --force')
            ->expectsOutput('Starting to mark absent students...')
            ->expectsOutput('Found 1 students without an attendance record. Marking them as absent...')
            ->assertExitCode(0);

        // 3. Assertions
        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $absentStudent->id,
            'date' => now()->toDateString(),
            'status' => AttendanceStatusEnum::TIDAK_HADIR->value,
        ]);

        // Ensure no record was created for the student who was already present.
        $this->assertDatabaseCount('student_attendances', 2);
    }

    /**
     * Test the command does nothing on a weekend.
     */
    public function test_does_nothing_on_a_weekend(): void
    {
        // Set the current time to a Saturday.
        Carbon::setTestNow(Carbon::parse('next saturday'));

        Student::factory()->create();

        $this->artisan('attendance:mark-absent --force')
            ->expectsOutput('Today is a weekend. No action taken.')
            ->assertExitCode(0);

        // Assert that no attendance record was created.
        $this->assertDatabaseCount('student_attendances', 0);
    }

    /**
     * Test the command does nothing on a holiday.
     */
    public function test_does_nothing_on_a_holiday(): void
    {
        Carbon::setTestNow(Carbon::parse('next monday'));

        // 1. Setup the data
        Student::factory()->create();
        Holiday::factory()->create([
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'description' => 'Test Holiday',
        ]);

        // 2. Execute the command
        $this->artisan('attendance:mark-absent --force')
            ->expectsOutput('Today is a holiday. No action taken.')
            ->assertExitCode(0);

        // 3. Assertions
        $this->assertDatabaseCount('student_attendances', 0);
    }

    /**
     * Test the command does nothing if all students are accounted for.
     */
    public function test_does_nothing_if_all_students_are_accounted_for(): void
    {
        Carbon::setTestNow(Carbon::parse('next monday'));

        // 1. Setup the data
        $student = Student::factory()->create();
        StudentAttendance::factory()->create([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'status' => AttendanceStatusEnum::SAKIT,
        ]);

        // 2. Execute the command
        $this->artisan('attendance:mark-absent --force')
            ->expectsOutput('All students have an attendance record for today. Nothing to do.')
            ->assertExitCode(0);

        // 3. Assertions
        // Ensure no new record was created.
        $this->assertDatabaseCount('student_attendances', 1);
    }
}
