<?php

namespace App\Livewire;

use App\Services\AttendanceCalendarService;
use App\Services\AttendanceDataService;
use App\Services\UserFinderService;
use App\Models\Student;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

class RealtimeAttendanceDashboard extends Component
{
    // Status mappings
    private const STATUS_COLORS = [
        'hadir' => 'bg-green-500',
        'tidak_hadir' => 'bg-red-500',
        'terlambat' => 'bg-yellow-500',
        'izin' => 'bg-blue-500',
        'sakit' => 'bg-blue-500',
        'holiday' => 'bg-gray-500',
        'no_data' => 'bg-gray-200',
        'error' => 'bg-gray-300',
    ];

    private const STATUS_TEXTS = [
        'hadir' => 'Hadir',
        'tidak_hadir' => 'Tidak Hadir',
        'terlambat' => 'Terlambat',
        'izin' => 'Izin',
        'sakit' => 'Sakit',
        'holiday' => 'Hari Libur',
    ];

    // Public properties
    public $currentUser = null;
    public $attendanceCalendar = [];
    public $currentMonth;
    public $currentYear;
    public $showDetails = false;
    public $lastScanTimestamp = null;
    public $lastScanId = null;

    // Services
    protected UserFinderService $userFinder;
    protected AttendanceDataService $attendanceDataService;
    protected AttendanceCalendarService $calendarService;

    // Event listeners
    protected $listeners = [
        'attendanceUpdated' => 'refreshData',
        'echo:attendance-dashboard,user.scanned' => 'handleRealtimeUserScanned'
    ];

    public function boot(
        UserFinderService $userFinder,
        AttendanceDataService $attendanceDataService,
        AttendanceCalendarService $calendarService
    ) {
        $this->userFinder = $userFinder;
        $this->attendanceDataService = $attendanceDataService;
        $this->calendarService = $calendarService;
    }

    // ================================================
    // LIFECYCLE METHODS
    // ================================================

    public function mount(): void
    {
        $this->initializeCurrentDate();
        $this->lastScanTimestamp = now();
        $this->logInfo('Dashboard mounted', ['month' => $this->currentMonth, 'year' => $this->currentYear]);
    }

    private function initializeCurrentDate(): void
    {
        $now = now();
        $this->currentMonth = $now->month;
        $this->currentYear = $now->year;
    }

    public function refreshData(): void
    {
        $this->logInfo('RefreshData called at: ' . now());
        $this->checkForNewScans();

        if ($this->currentUser) {
            $this->loadAttendanceCalendar();
        }
    }

    public function checkForNewScans(): void
    {
        $recentAttendance = $this->userFinder->findMostRecentAttendance($this->lastScanTimestamp);
        if ($recentAttendance) {
            $user = $recentAttendance->student ?? $recentAttendance->teacher;

            if ($user) {
                $this->lastScanTimestamp = $recentAttendance->created_at->toIso8601String();
                $this->handleUserScanned($user->fingerprint_id);
            }
        }


    }

    public function handleRealtimeUserScanned(array $event): void
    {
        if (!empty($event['fingerprint_id'])) {
            $this->handleUserScanned($event['fingerprint_id']);
        }
    }

    #[On('user-scanned')]
    public function handleUserScanned(string $fingerprintId): void
    {
        $user = $this->userFinder->findByFingerprint($fingerprintId);
        if (!$user) return;

        $this->currentUser = $user;
        $this->showDetails = true;
        $this->loadAttendanceCalendar();

        $this->dispatch('reset-auto-hide');
        $this->dispatch('start-auto-hide');
    }

    // ================================================
    // UI CONTROL METHODS
    // ================================================

    public function hideDetails(): void
    {
        $this->showDetails = false;
        $this->currentUser = null;
        $this->attendanceCalendar = [];
        $this->logInfo('Details hidden');
    }

    public function testCalendar(): void
    {
        if (!app()->environment('local')) return;

        $student = $this->userFinder->findTestStudent();
        if (!$student) {
            $this->logInfo('No student with attendance found for testing');
            return;
        }

        $this->logInfo('Test calendar triggered', [
            'student_name' => $student->name,
            'fingerprint_id' => $student->fingerprint_id
        ]);

        $this->handleUserScanned($student->fingerprint_id);
    }

    // ================================================
    // CALENDAR MANAGEMENT
    // ================================================

    private function loadAttendanceCalendar(): void
    {
        if (!$this->currentUser) return;

        [$startDate, $endDate] = $this->getMonthDateRange();

        // 1. Fetch data using the data service
        $attendances = $this->attendanceDataService->fetchAttendances($this->currentUser, $startDate, $endDate);
        $holidays = $this->attendanceDataService->fetchHolidays($startDate, $endDate);
        $attendanceRules = $this->attendanceDataService->fetchAttendanceRules($this->currentUser);

        // 2. Build calendar using the calendar service
        $this->attendanceCalendar = $this->calendarService->generateCalendar(
            $this->currentYear,
            $this->currentMonth,
            $attendances,
            $holidays,
            $attendanceRules
        );

        $this->logInfo('Calendar loaded', ['user_name' => $this->currentUser->name]);
    }

    private function getMonthDateRange(): array
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1);
        return [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
    }

    // ================================================
    // NAVIGATION
    // ================================================

    public function previousMonth(): void
    {
        $this->navigateMonth(-1);
    }

    public function nextMonth(): void
    {
        $this->navigateMonth(1);
    }

    private function navigateMonth(int $direction): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonths($direction);
        $this->currentYear = $date->year;
        $this->currentMonth = $date->month;

        $this->logInfo('Month navigation', ['new_month' => $this->currentMonth, 'new_year' => $this->currentYear]);
        $this->loadAttendanceCalendar();
    }

    // ================================================
    // STATUS DISPLAY
    // ================================================

    public function getStatusColor(string $status): string
    {
        return self::STATUS_COLORS[$status] ?? self::STATUS_COLORS['no_data'];
    }

    public function getStatusText(string $status): string
    {
        return self::STATUS_TEXTS[$status] ?? 'Tidak Ada Data';
    }

    // ================================================
    // LOGGING
    // ================================================

    private function logInfo(string $message, array $context = []): void
    {
        Log::info('[RealtimeAttendanceDashboard] ' . $message, $context);
    }

    public function render()
    {
        return view('livewire.realtime-attendance-dashboard', [
            'monthName' => Carbon::create($this->currentYear, $this->currentMonth, 1)->format('F Y')
        ])->layout('layouts.dashboard-standalone');
    }
}
