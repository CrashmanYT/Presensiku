<?php

namespace Tests\Feature\Webhooks;

use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentLeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    /** @test */
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

        // Pastikan data absensi disinkronkan
        $this->assertDatabaseHas('student_attendances', ['date' => '2025-08-01', 'status' => 'sakit']);
        $this->assertDatabaseHas('student_attendances', ['date' => '2025-08-02', 'status' => 'sakit']);
        $this->assertDatabaseHas('student_attendances', ['date' => '2025-08-03', 'status' => 'sakit']);
    }

    /** @test */
    public function it_rejects_request_with_invalid_secret_token()
    {
        $payload = ['identifier' => '12345'];

        $response = $this->withHeaders(['X-Webhook-Secret' => 'wrong-token'])
                         ->postJson($this->studentWebhookUrl, $payload);

        // Otorisasi gagal akan mengembalikan status 403
        $response->assertStatus(403);
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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
}