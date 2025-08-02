<?php

namespace Tests\Feature\Webhooks;

use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentLeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeaveRequestWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $secretToken;
    private string $studentWebhookUrl;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Siapkan environment untuk testing
        $this->secretToken = 'test-secret-token';
        config(['services.webhook.secret_token' => $this->secretToken]);

        $this->studentWebhookUrl = '/api/webhook/student-leave-request';

        // 2. Buat data dummy yang akan digunakan di semua test
        $this->student = Student::factory()->create(['nis' => '12345']);
    }

    #[Test]
    public function it_successfully_creates_a_leave_request_and_syncs_attendance()
    {
        $payload = [
            'identifier' => '12345',
            'type' => 'Sakit',
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-03',
            'reason' => 'Demam tinggi',
        ];

        // Kirim request ke webhook
        $response = $this->withHeaders(['X-Webhook-Secret' => $this->secretToken])
                         ->postJson($this->studentWebhookUrl, $payload);

        // Pastikan respon sukses
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Izin Siswa Berhasil Diproses']);

        // Pastikan data dibuat di database
        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->student->id,
            'type' => 'sakit',
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-03',
        ]);

        // Pastikan data absensi disinkronkan (hanya untuk hari kerja)
        $this->assertDatabaseHas('student_attendances', ['date' => '2025-08-01', 'status' => 'sakit']);
        $this->assertDatabaseMissing('student_attendances', ['date' => '2025-08-02']); // Weekend
        $this->assertDatabaseMissing('student_attendances', ['date' => '2025-08-03']); // Weekend
    }

    #[Test]
    public function it_rejects_request_with_invalid_secret_token()
    {
        $payload = ['identifier' => '12345'];

        $response = $this->withHeaders(['X-Webhook-Secret' => 'wrong-token'])
                         ->postJson($this->studentWebhookUrl, $payload);

        // Otorisasi gagal akan mengembalikan status 403
        $response->assertStatus(403);
    }

    #[Test]
    public function it_rejects_request_with_invalid_data()
    {
        $payload = [
            'identifier' => '12345',
            'type' => 'Liburan', // Tipe tidak valid
            'start_date' => '2025-08-01',
            'end_date' => '2025-07-30', // Tanggal selesai sebelum mulai
        ];

        $response = $this->withHeaders(['X-Webhook-Secret' => $this->secretToken])
                         ->postJson($this->studentWebhookUrl, $payload);

        // Validasi gagal akan mengembalikan status 422
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['type', 'end_date']);
    }

    #[Test]
    public function it_returns_not_found_if_student_does_not_exist()
    {
        $payload = [
            'identifier' => '99999', // NIS tidak ada
            'type' => 'Sakit',
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-03',
            'reason' => 'Demam tinggi',
        ];

        $response = $this->withHeaders(['X-Webhook-Secret' => $this->secretToken])
                         ->postJson($this->studentWebhookUrl, $payload);

        $response->assertStatus(404)
                 ->assertJson(['message' => 'Siswa Dengan NIS Tersebut Tidak Ditemukan']);
    }

    #[Test]
    public function it_correctly_trims_and_updates_overlapping_leave_requests()
    {
        // Buat data izin lama di database: 1 - 7 Agustus (Sakit)
        StudentLeaveRequest::factory()->create([
            'student_id' => $this->student->id,
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-07',
            'type' => 'sakit',
        ]);

        $payload = [
            'identifier' => '12345',
            'type' => 'Izin',
            'start_date' => '2025-08-06', // Tumpang tindih
            'end_date' => '2025-08-10',
            'reason' => 'Acara keluarga',
        ];

        $this->withHeaders(['X-Webhook-Secret' => $this->secretToken])
             ->postJson($this->studentWebhookUrl, $payload)
             ->assertStatus(200);

        // Pastikan record lama sudah "dipotong"
        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->student->id,
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-05', // Dipotong menjadi sehari sebelum mulai
            'type' => 'sakit',
        ]);

        // Pastikan record baru dibuat
        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->student->id,
            'start_date' => '2025-08-06',
            'end_date' => '2025-08-10',
            'type' => 'izin',
        ]);
    }

    #[Test]
    public function it_correctly_splits_an_existing_leave_request_when_a_new_one_is_in_the_middle()
    {
        // 1. Buat data izin lama yang panjang (1-10 Agustus)
        StudentLeaveRequest::factory()->create([
            'student_id' => $this->student->id,
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-10',
            'type' => 'sakit',
            'reason' => 'Sakit demam',
        ]);

        // 2. Siapkan payload untuk izin baru di tengah-tengah (4-6 Agustus)
        $payload = [
            'identifier' => '12345',
            'type' => 'Izin',
            'start_date' => '2025-08-04',
            'end_date' => '2025-08-06',
            'reason' => 'Acara keluarga',
        ];

        // 3. Kirim request
        $this->withHeaders(['X-Webhook-Secret' => $this->secretToken])
            ->postJson($this->studentWebhookUrl, $payload)
            ->assertStatus(200);

        // 4. Verifikasi hasilnya
        // Pastikan record lama terpotong menjadi bagian pertama (1-3 Agustus)
        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->student->id,
            'type' => 'sakit',
            'start_date' => '2025-08-01',
            'end_date' => '2025-08-03',
        ]);

        // Pastikan record baru dibuat (4-6 Agustus)
        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->student->id,
            'type' => 'izin',
            'start_date' => '2025-08-04',
            'end_date' => '2025-08-06',
        ]);

        // Pastikan sisa dari record lama menjadi record baru (7-10 Agustus)
        $this->assertDatabaseHas('student_leave_requests', [
            'student_id' => $this->student->id,
            'type' => 'sakit',
            'start_date' => '2025-08-07',
            'end_date' => '2025-08-10',
        ]);

        // Pastikan hanya ada 3 record untuk siswa ini
        $this->assertDatabaseCount('student_leave_requests', 3);
    }

    #[Test]
    public function it_skips_weekends_and_holidays_when_syncing_attendance()
    {
        // Friday, August 1, 2025, to Tuesday, August 5, 2025
        // This range includes a weekend (Aug 2, Aug 3)
        $startDate = '2025-08-01'; // Friday
        $endDate = '2025-08-05';   // Tuesday

        // Let's make Monday a public holiday
        $holidayDate = '2025-08-04'; // Monday
        \App\Models\Holiday::factory()->create([
            'start_date' => $holidayDate,
            'end_date' => $holidayDate,
            'description' => 'Test Holiday'
        ]);

        $payload = [
            'identifier' => '12345',
            'type' => 'Sakit',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => 'Flu',
        ];

        $this->withHeaders(['X-Webhook-Secret' => $this->secretToken])
            ->postJson($this->studentWebhookUrl, $payload)
            ->assertStatus(200);

        // Assert that attendance WAS created for the working days
        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $this->student->id,
            'date' => '2025-08-01', // Friday
            'status' => 'sakit',
        ]);
        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $this->student->id,
            'date' => '2025-08-05', // Tuesday
            'status' => 'sakit',
        ]);

        // Assert that attendance was NOT created for the weekend and holiday
        $this->assertDatabaseMissing('student_attendances', [
            'student_id' => $this->student->id,
            'date' => '2025-08-02', // Saturday
        ]);
        $this->assertDatabaseMissing('student_attendances', [
            'student_id' => $this->student->id,
            'date' => '2025-08-03', // Sunday
        ]);
        $this->assertDatabaseMissing('student_attendances', [
            'student_id' => $this->student->id,
            'date' => $holidayDate, // Monday (Holiday)
        ]);

        // Finally, ensure only 2 attendance records were created in total for this request
        $this->assertDatabaseCount('student_attendances', 2);
    }
}