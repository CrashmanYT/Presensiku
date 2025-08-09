<?php

namespace Tests\Unit\Services;

use App\Models\Classes;
use App\Models\Device;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Holiday;
use App\Models\AttendanceRule;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use App\Services\AttendanceDataService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDataServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AttendanceDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttendanceDataService();
        // Add a device to prevent factory errors
        Device::factory()->create();
    }

    public function test_fetch_attendances_for_student()
    {
        $student = Student::factory()->create();
        StudentAttendance::factory()->create([
            'student_id' => $student->id,
            'date' => '2025-08-01',
        ]);
        StudentAttendance::factory()->create([
            'student_id' => $student->id,
            'date' => '2025-08-05',
        ]);

        $startDate = Carbon::parse('2025-08-01');
        $endDate = Carbon::parse('2025-08-31');

        $attendances = $this->service->fetchAttendances($student, $startDate, $endDate);

        $this->assertCount(2, $attendances);
        $this->assertTrue($attendances->has('2025-08-01'));
        $this->assertTrue($attendances->has('2025-08-05'));
    }

    public function test_fetch_attendances_for_teacher()
    {
        $teacher = Teacher::factory()->create();
        TeacherAttendance::factory()->create([
            'teacher_id' => $teacher->id,
            'date' => '2025-08-02',
        ]);

        $startDate = Carbon::parse('2025-08-01');
        $endDate = Carbon::parse('2025-08-31');

        $attendances = $this->service->fetchAttendances($teacher, $startDate, $endDate);

        $this->assertCount(1, $attendances);
        $this->assertTrue($attendances->has('2025-08-02'));
    }

    public function test_fetch_holidays()
    {
        Holiday::factory()->create(['start_date' => '2025-08-17', 'end_date' => '2025-08-17']); // Inside range
        Holiday::factory()->create(['start_date' => '2025-07-28', 'end_date' => '2025-08-02']); // Overlapping start
        Holiday::factory()->create(['start_date' => '2025-08-28', 'end_date' => '2025-09-05']); // Overlapping end
        Holiday::factory()->create(['start_date' => '2025-07-01', 'end_date' => '2025-09-30']); // Enclosing range

        $startDate = Carbon::parse('2025-08-01');
        $endDate = Carbon::parse('2025-08-31');

        $holidays = $this->service->fetchHolidays($startDate, $endDate);

        $this->assertCount(4, $holidays);
    }

    public function test_fetch_attendance_rules_for_student_with_class()
    {
        $class = Classes::factory()->create();
        $student = Student::factory()->create(['class_id' => $class->id]);
        AttendanceRule::factory()->count(3)->create(['class_id' => $class->id]);
        AttendanceRule::factory()->count(2)->create(); // Rules for other classes

        $rules = $this->service->fetchAttendanceRules($student);

        $this->assertCount(3, $rules);
        $this->assertEquals($class->id, $rules->first()->class_id);
    }

    public function test_fetch_attendance_rules_for_student_without_class_returns_empty()
    {
        // Since class_id is not nullable, we test the scenario where a student might not have a class relationship
        // This test now verifies that a student without a class returns an empty collection of rules.
        $student = Student::factory()->create(); // Student will have a class by default from factory
        $student->class_id = null; // Manually setting to null for the test case if schema were to allow it
        $student->unsetRelation('class'); // Detach the relationship to simulate no class

        $rules = $this->service->fetchAttendanceRules($student);

        $this->assertCount(0, $rules);
    }

    public function test_fetch_attendance_rules_for_teacher()
    {
        $teacher = Teacher::factory()->create();
        $rules = $this->service->fetchAttendanceRules($teacher);
        $this->assertCount(0, $rules);
    }
}
