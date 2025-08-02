<?php

namespace Tests\Unit\Services;

use App\Models\Student;
use App\Models\StudentLeaveRequest;
use App\Services\LeaveRequestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class LeaveRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveRequestService $leaveRequestService;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->leaveRequestService = new LeaveRequestService();
        $this->student = Student::factory()->create();
    }

    /**
     * Helper function to call private methods for testing purposes.
     */
    private function callPrivateMethod($object, $methodName, array $parameters)
    {
        $reflection = new ReflectionMethod(get_class($object), $methodName);
        return $reflection->invokeArgs($object, $parameters);
    }

    #[Test]
    public function it_correctly_splits_an_existing_request_when_a_new_one_is_in_the_middle()
    {
        // Arrange: Buat satu izin lama yang panjang
        StudentLeaveRequest::factory()->create([
            'student_id' => $this->student->id,
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-10',
            'type' => 'sakit',
        ]);

        $newStartDate = Carbon::parse('2025-08-04');
        $newEndDate = Carbon::parse('2025-08-06');

        // Act: Panggil method privat handleOverlaps
        $this->callPrivateMethod($this->leaveRequestService, 'handleOverlaps', [
            new StudentLeaveRequest(),
            $this->student->id,
            $newStartDate,
            $newEndDate
        ]);

        // Assert: Pastikan hasilnya benar
        $this->assertDatabaseCount('student_leave_requests', 2);

        // 1. Bagian pertama dari izin lama
        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->student->id,
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-03', // Dipotong
            'type' => 'sakit',
        ]);

        // 2. Bagian kedua dari izin lama (dibuat sebagai record baru)
        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->student->id,
            'start_date' => '2025-08-07', // Dimulai setelah izin baru
            'end_date' => '2025-08-10',
            'type' => 'sakit',
        ]);
    }

    #[Test]
    public function it_deletes_an_old_request_when_it_is_completely_swallowed_by_a_new_one()
    {
        // Arrange: Buat satu izin lama yang pendek
        $oldRequest = StudentLeaveRequest::factory()->create([
            'student_id' => $this->student->id,
            'start_date' => '2025-08-04',
            'end_date' => '2025-08-06',
        ]);

        // Izin baru yang "menelan" izin lama
        $newStartDate = Carbon::parse('2025-08-01');
        $newEndDate = Carbon::parse('2025-08-10');

        // Act
        $this->callPrivateMethod($this->leaveRequestService, 'handleOverlaps', [
            new StudentLeaveRequest(),
            $this->student->id,
            $newStartDate,
            $newEndDate
        ]);

        // Assert: Pastikan izin lama sudah dihapus
        $this->assertDatabaseMissing('student_leave_requests', [
            'id' => $oldRequest->id,
        ]);
        $this->assertDatabaseCount('student_leave_requests', 0);
    }

    #[Test]
    public function it_trims_the_end_date_of_an_old_request_when_overlapped_at_the_end()
    {
        // Arrange
        StudentLeaveRequest::factory()->create([
            'student_id' => $this->student->id,
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-07',
        ]);

        $newStartDate = Carbon::parse('2025-08-06');
        $newEndDate = Carbon::parse('2025-08-10');

        // Act
        $this->callPrivateMethod($this->leaveRequestService, 'handleOverlaps', [
            new StudentLeaveRequest(),
            $this->student->id,
            $newStartDate,
            $newEndDate
        ]);

        // Assert
        $this->assertDatabaseCount('student_leave_requests', 1);
        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->student->id,
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-05', // Seharusnya dipotong menjadi sehari sebelum izin baru
        ]);
    }

    #[Test]
    public function it_trims_the_start_date_of_an_old_request_when_overlapped_at_the_start()
    {
        // Arrange
        StudentLeaveRequest::factory()->create([
            'student_id' => $this->student->id,
            'start_date' => '2025-08-05',
            'end_date' => '2025-08-10',
        ]);

        $newStartDate = Carbon::parse('2025-08-01');
        $newEndDate = Carbon::parse('2025-08-06');

        // Act
        $this->callPrivateMethod($this->leaveRequestService, 'handleOverlaps', [
            new StudentLeaveRequest(),
            $this->student->id,
            $newStartDate,
            $newEndDate
        ]);

        // Assert
        $this->assertDatabaseCount('student_leave_requests', 1);
        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->student->id,
            'start_date' => '2025-08-07', // Seharusnya dimulai sehari setelah izin baru
            'end_date' => '2025-08-10',
        ]);
    }

    #[Test]
    public function it_can_process_a_leave_request_from_webhook_and_sync_attendance()
    {
        // Arrange: Create a long-standing leave request that will be split.
        StudentLeaveRequest::factory()->create([
            'student_id' => $this->student->id,
            'start_date' => '2025-08-01', // Friday
            'end_date' => '2025-08-10',   // Sunday
            'type' => 'sakit',
            'reason' => 'Sakit demam',
        ]);

        // New leave request data from a webhook, which is in the middle of the old one.
        $webhookData = [
            'start_date' => '2025-08-04', // Monday
            'end_date'   => '2025-08-06', // Wednesday
            'type'       => 'izin',
            'reason'     => 'Acara keluarga',
            'attachment' => null,
        ];

        // Act: Call the public method that is being tested.
        $this->leaveRequestService->processFromWebhook($this->student, $webhookData);

        // Assert: Check the state of leave requests after the operation.
        // We expect the original request to be split and the new one to be created. Total of 3 requests.
        $this->assertDatabaseCount('student_leave_requests', 3);

        // 1. The new leave request created from the webhook.
        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->student->id,
            'start_date' => '2025-08-04',
            'end_date'   => '2025-08-06',
            'type'       => 'izin',
            'reason'     => 'Acara keluarga',
        ]);

        // 2. The first part of the original leave request (trimmed).
        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->student->id,
            'start_date' => '2025-08-01',
            'end_date'   => '2025-08-03', // Trimmed from Aug 10th.
            'type'       => 'sakit',
        ]);

        // 3. The second part of the original leave request (newly created).
        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->student->id,
            'start_date' => '2025-08-07', // Starts after the new request ends.
            'end_date'   => '2025-08-10',
            'type'       => 'sakit',
        ]);

        // Assert: Check that the attendance records were created for the new leave request dates.
        // The dates are Mon, Tue, Wed, which are working days.
        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $this->student->id,
            'date'       => '2025-08-04',
            'status'     => 'izin',
        ]);
        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $this->student->id,
            'date'       => '2025-08-05',
            'status'     => 'izin',
        ]);
        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $this->student->id,
            'date'       => '2025-08-06',
            'status'     => 'izin',
        ]);

        // Make sure no attendance record was created for the weekend within the old request range.
        $this->assertDatabaseMissing('student_attendances', [
            'student_id' => $this->student->id,
            'date'       => '2025-08-02', // Saturday
        ]);
        $this->assertDatabaseMissing('student_attendances', [
            'student_id' => $this->student->id,
            'date'       => '2025-08-03', // Sunday
        ]);
    }
}
