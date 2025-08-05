<?php

namespace Tests\Unit;

use App\Models\AttendanceRule;
use App\Models\Classes;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Services\LeaveRequestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BugFixVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test TeacherAttendance.detectScanType() method fix
     */
    public function test_teacher_attendance_detect_scan_type_fix()
    {
        // Create test data
        $class = Classes::factory()->create();
        $teacher = Teacher::factory()->create();

        $attendanceRule = AttendanceRule::create([
            'class_id' => $class->id,
            'time_in_start' => '07:00:00',
            'time_in_end' => '08:00:00',
            'time_out_start' => '15:00:00',
            'time_out_end' => '16:00:00',
            'day_of_week' => [1, 2, 3, 4, 5], // Monday to Friday
        ]);

        $attendance = new TeacherAttendance([
            'teacher_id' => $teacher->id,
            'date' => now()->toDateString(),
            'status' => 'hadir',
        ]);

        // Test scan in time
        $scanTime = Carbon::parse('07:30:00');
        $result = $attendance->detectScanType($scanTime, $attendanceRule);
        $this->assertEquals('in', $result);

        // Set time_in and test scan out time
        $attendance->time_in = Carbon::parse('07:30:00');
        $scanTime = Carbon::parse('15:30:00');
        $result = $attendance->detectScanType($scanTime, $attendanceRule);
        $this->assertEquals('out', $result);

        echo "✅ TeacherAttendance.detectScanType() method fix verified\n";
    }

    /**
     * Test AttendanceRule data type casting fix
     */
    public function test_attendance_rule_time_casting_fix()
    {
        $class = Classes::factory()->create();

        $attendanceRule = AttendanceRule::create([
            'class_id' => $class->id,
            'time_in_start' => '07:00:00',
            'time_in_end' => '08:00:00',
            'time_out_start' => '15:00:00',
            'time_out_end' => '16:00:00',
            'day_of_week' => [1, 2, 3, 4, 5],
        ]);

        // Verify that time fields are properly cast
        $this->assertInstanceOf(\Carbon\Carbon::class, $attendanceRule->time_in_start);
        $this->assertInstanceOf(\Carbon\Carbon::class, $attendanceRule->time_in_end);
        $this->assertInstanceOf(\Carbon\Carbon::class, $attendanceRule->time_out_start);
        $this->assertInstanceOf(\Carbon\Carbon::class, $attendanceRule->time_out_end);

        // Verify time format
        $this->assertEquals('07:00:00', $attendanceRule->time_in_start->format('H:i:s'));
        $this->assertEquals('08:00:00', $attendanceRule->time_in_end->format('H:i:s'));

        echo "✅ AttendanceRule time casting fix verified\n";
    }

    /**
     * Test Student notifications relationship fix
     */
    public function test_student_notifications_relationship_fix()
    {
        $class = Classes::factory()->create();
        $student = Student::factory()->create(['class_id' => $class->id]);

        // Test that notifications relationship doesn't throw error
        $notifications = $student->notifications();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $notifications);

        echo "✅ Student notifications relationship fix verified\n";
    }

    /**
     * Test LeaveRequestService input validation fix
     */
    public function test_leave_request_service_validation_fix()
    {
        $class = Classes::factory()->create();
        $student = Student::factory()->create(['class_id' => $class->id]);
        $service = new LeaveRequestService;

        // Test with invalid data (missing required fields)
        $invalidData = [
            'start_date' => '2025-08-04',
            // Missing end_date, type, reason
        ];

        $this->expectException(\InvalidArgumentException::class);
        $service->processFromWebhook($student, $invalidData);

        echo "✅ LeaveRequestService input validation fix verified\n";
    }

    /**
     * Test unique constraints on attendance tables
     */
    public function test_attendance_unique_constraints()
    {
        $class = Classes::factory()->create();
        $student = Student::factory()->create(['class_id' => $class->id]);
        $teacher = Teacher::factory()->create();

        $date = now()->toDateString();

        // Create first attendance record
        $studentAttendance1 = \App\Models\StudentAttendance::create([
            'student_id' => $student->id,
            'date' => $date,
            'status' => 'hadir',
        ]);

        $teacherAttendance1 = \App\Models\TeacherAttendance::create([
            'teacher_id' => $teacher->id,
            'date' => $date,
            'status' => 'hadir',
        ]);

        // Try to create duplicate - should fail due to unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);

        \App\Models\StudentAttendance::create([
            'student_id' => $student->id,
            'date' => $date,
            'status' => 'terlambat',
        ]);

        echo "✅ Attendance unique constraints verified\n";
    }
}
