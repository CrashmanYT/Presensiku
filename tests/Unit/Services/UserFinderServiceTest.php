<?php

namespace Tests\Unit\Services;

use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use App\Services\UserFinderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserFinderServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserFinderService $userFinderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userFinderService = new UserFinderService;
    }

    // --- Tes untuk findByFingerprint ---

    #[Test]
    public function it_finds_a_student_by_fingerprint_id()
    {
        // Arrange: Buat seorang siswa dengan fingerprint ID tertentu
        $student = Student::factory()->create(['fingerprint_id' => '12345']);
        // Buat juga seorang guru dengan ID yang sama untuk memastikan siswa diprioritaskan
        Teacher::factory()->create(['fingerprint_id' => '12345']);

        // Act: Panggil method yang diuji
        $foundUser = $this->userFinderService->findByFingerprint('12345');

        // Assert: Pastikan pengguna yang ditemukan adalah siswa
        $this->assertNotNull($foundUser);
        $this->assertInstanceOf(Student::class, $foundUser);
        $this->assertEquals($student->id, $foundUser->id);
    }

    #[Test]
    public function it_finds_a_teacher_if_no_student_matches_fingerprint_id()
    {
        // Arrange: Buat seorang guru, tapi tidak ada siswa dengan ID ini
        $teacher = Teacher::factory()->create(['fingerprint_id' => '67890']);

        // Act
        $foundUser = $this->userFinderService->findByFingerprint('67890');

        // Assert
        $this->assertNotNull($foundUser);
        $this->assertInstanceOf(Teacher::class, $foundUser);
        $this->assertEquals($teacher->id, $foundUser->id);
    }

    #[Test]
    public function it_returns_null_if_no_user_matches_fingerprint_id()
    {
        // Arrange: Pastikan tidak ada pengguna dengan ID ini
        // (database sudah bersih berkat RefreshDatabase)

        // Act
        $foundUser = $this->userFinderService->findByFingerprint('non_existent_id');

        // Assert
        $this->assertNull($foundUser);
    }

    // --- Tes untuk findMostRecentAttendance ---

    #[Test]
    public function it_returns_the_most_recent_student_attendance()
    {
        // Arrange: Buat absensi guru yang lebih lama
        TeacherAttendance::factory()->create([
            'created_at' => now()->subMinute(),
        ]);

        // Buat absensi siswa yang paling baru
        $recentStudentAttendance = StudentAttendance::factory()->create([
            'created_at' => now(),
        ]);

        // Act
        $mostRecent = $this->userFinderService->findMostRecentAttendance();

        // Assert
        $this->assertNotNull($mostRecent);
        $this->assertInstanceOf(StudentAttendance::class, $mostRecent);
        $this->assertEquals($recentStudentAttendance->id, $mostRecent->id);
    }

    #[Test]
    public function it_returns_the_most_recent_teacher_attendance()
    {
        // Arrange: Buat satu device agar factory tidak gagal
        \App\Models\Device::factory()->create();

        // Buat absensi siswa yang lebih lama
        StudentAttendance::factory()->create([
            'created_at' => now()->subMinute(),
        ]);

        // Buat absensi guru yang paling baru
        $recentTeacherAttendance = TeacherAttendance::factory()->create([
            'created_at' => now(),
        ]);

        // Act
        $mostRecent = $this->userFinderService->findMostRecentAttendance();

        // Assert
        $this->assertNotNull($mostRecent);
        $this->assertInstanceOf(TeacherAttendance::class, $mostRecent);
        $this->assertEquals($recentTeacherAttendance->id, $mostRecent->id);
    }

    #[Test]
    public function it_correctly_filters_attendance_by_timestamp()
    {
        // Arrange
        \App\Models\Device::factory()->create();
        $oneMinuteAgo = now()->subMinute();
        $now = now();

        // Absensi lama yang seharusnya tidak ditemukan
        StudentAttendance::factory()->create(['created_at' => now()->subMinutes(2)]);
        // Absensi yang seharusnya menjadi kandidat, tapi bukan yang terbaru
        TeacherAttendance::factory()->create(['created_at' => $oneMinuteAgo]);
        // Absensi terbaru yang seharusnya ditemukan
        $mostRecentStudentAttendance = StudentAttendance::factory()->create(['created_at' => $now]);

        // Act: Cari absensi yang lebih baru dari satu menit yang lalu
        $mostRecent = $this->userFinderService->findMostRecentAttendance($oneMinuteAgo);

        // Assert
        $this->assertNotNull($mostRecent);
        $this->assertInstanceOf(StudentAttendance::class, $mostRecent);
        $this->assertEquals($mostRecentStudentAttendance->id, $mostRecent->id);
    }
}
