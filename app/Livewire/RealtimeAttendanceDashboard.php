<?php

namespace App\Livewire;

use App\Services\AttendanceCalendarService;
use App\Services\UserFinderService;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use App\Models\Holiday;
use App\Models\AttendanceRule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
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
        'holiday' => 'bg-purple-500',
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
    public $lastScanId = null;
    
    // Services
    protected AttendanceCalendarService $calendarService;
    protected UserFinderService $userFinder;
    
    // Event listeners
    protected $listeners = [
        'attendanceUpdated' => 'refreshData',
        'echo:attendance-dashboard,user.scanned' => 'handleRealtimeUserScanned'
    ];

    public function boot(
        AttendanceCalendarService $calendarService,
        UserFinderService $userFinder
    ) {
        $this->calendarService = $calendarService;
        $this->userFinder = $userFinder;
    }

    // ================================================
    // LIFECYCLE METHODS
    // ================================================

    public function mount(): void
    {
        $this->initializeCurrentDate();
        $this->logInfo('Dashboard mounted', $this->getCurrentDateContext());
    }

    private function initializeCurrentDate(): void
    {
        $now = now();
        $this->currentMonth = $now->month;
        $this->currentYear = $now->year;
    }

    private function getCurrentDateContext(): array
    {
        return [
            'month' => $this->currentMonth,
            'year' => $this->currentYear
        ];
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
        $recentAttendance = $this->userFinder->findRecentAttendance($this->lastScanId);

        if (!$recentAttendance || !$recentAttendance->student) {
            return;
        }

        $this->lastScanId = $recentAttendance->id;
        $this->handleUserScanned($recentAttendance->student->fingerprint_id);
    }

    public function handleRealtimeUserScanned(array $event): void
    {
        if (empty($event['fingerprint_id'])) {
            return;
        }

        $this->handleUserScanned($event['fingerprint_id']);
    }

    #[On('user-scanned')]
    public function handleUserScanned(string $fingerprintId): void
    {
        $user = $this->userFinder->findByFingerprint($fingerprintId);

        if (!$user) {
            return;
        }

        $this->currentUser = $user;
        $this->showDetails = true;
        $this->loadAttendanceCalendar();

        if ($this->showDetails) {
            $this->dispatch('reset-auto-hide');
        }
        
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
        if (!app()->environment('local')) {
            $this->logInfo('Test calendar only available in local environment');
            return;
        }

        $student = $this->findTestStudent();
        
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

    private function findTestStudent()
    {
        return Student::has('attendances')
            ->whereNotNull('fingerprint_id')
            ->first();
    }

    // ================================================
    // CALENDAR MANAGEMENT METHODS
    // ================================================

    private function loadAttendanceCalendar(): void
    {
        if (!$this->currentUser) return;

        [$startDate, $endDate] = $this->getMonthDateRange();
        $this->logCalendarLoadInfo($startDate, $endDate);
        
        $attendances = $this->fetchAttendances($startDate, $endDate);
        $holidays = $this->fetchHolidays($startDate, $endDate);
        $attendanceRules = $this->fetchAttendanceRules();
        
        $this->attendanceCalendar = $this->buildCalendar($startDate, $attendances, $holidays, $attendanceRules);
        $this->logInfo('Calendar built with ' . count($this->attendanceCalendar) . ' days');
    }

    private function getMonthDateRange(): array
    {
        $startDate = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $endDate = $startDate->copy()->endOfMonth();
        return [$startDate, $endDate];
    }

    private function logCalendarLoadInfo(Carbon $startDate, Carbon $endDate): void
    {
        $this->logInfo('Loading attendance calendar', [
            'user_name' => $this->currentUser->name,
            'user_id' => $this->currentUser->id,
            'date_range' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d')
        ]);
    }

    private function fetchAttendances(Carbon $startDate, Carbon $endDate): Collection
    {
        $dateRange = [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')];
        
        if ($this->currentUser instanceof Student) {
            $attendances = StudentAttendance::where('student_id', $this->currentUser->id)
                ->whereBetween('date', $dateRange)
                ->get();
        } else {
            $attendances = TeacherAttendance::where('teacher_id', $this->currentUser->id)
                ->whereBetween('date', $dateRange)
                ->get();
        }

        $this->logAttendanceRecords($attendances);
        
        return $attendances->keyBy(function ($item) {
            return $item->date->format('Y-m-d');
        });
    }

    private function logAttendanceRecords(Collection $attendances): void
    {
        $this->logInfo('Found ' . $attendances->count() . ' attendance records');
        
        foreach ($attendances as $attendance) {
            $statusValue = $this->extractStatusValue($attendance->status);
            $this->logInfo('Attendance record', [
                'date' => $attendance->date->format('Y-m-d'),
                'status' => $statusValue
            ]);
        }
    }

    private function fetchHolidays(Carbon $startDate, Carbon $endDate): Collection
    {
        return Holiday::where(function ($query) use ($startDate, $endDate) {
            $startDateStr = $startDate->format('Y-m-d');
            $endDateStr = $endDate->format('Y-m-d');
            
            $query->whereBetween('start_date', [$startDateStr, $endDateStr])
                  ->orWhereBetween('end_date', [$startDateStr, $endDateStr])
                  ->orWhere(function ($q) use ($startDateStr, $endDateStr) {
                      $q->where('start_date', '<=', $startDateStr)
                        ->where('end_date', '>=', $endDateStr);
                  });
        })->get();
    }

    private function fetchAttendanceRules(): Collection
    {
        if (!($this->currentUser instanceof Student) || !$this->currentUser->class) {
            return collect();
        }
        
        return AttendanceRule::where('class_id', $this->currentUser->class->id)->get();
    }

    private function buildCalendar(Carbon $startDate, Collection $attendances, Collection $holidays, Collection $attendanceRules): array
    {
        $calendar = [];
        $currentDate = $startDate->copy();

        while ($currentDate->month == $this->currentMonth) {
            $dayData = $this->buildCalendarDay($currentDate, $attendances, $holidays, $attendanceRules);
            $calendar[] = $dayData;
            
            $this->logInfo('Calendar day built', [
                'day' => $currentDate->day,
                'date' => $dayData['full_date'],
                'status' => $dayData['status']
            ]);
            
            $currentDate->addDay();
        }

        return $calendar;
    }

    private function buildCalendarDay(Carbon $date, Collection $attendances, Collection $holidays, Collection $attendanceRules): array
    {
        $dateString = $date->format('Y-m-d');
        $attendance = $attendances->get($dateString);
        
        $status = $attendance 
            ? $this->extractStatusValue($attendance->status)
            : $this->determineNoAttendanceStatus($date, $holidays, $attendanceRules);

        return [
            'date' => $date->day,
            'full_date' => $dateString,
            'status' => $status,
            'is_today' => $date->isToday(),
            'is_weekend' => $date->isWeekend(),
        ];
    }

    private function determineNoAttendanceStatus(Carbon $date, Collection $holidays, Collection $attendanceRules): string
    {
        $isHoliday = $this->isHoliday($date, $holidays);
        $shouldHaveAttendance = $this->shouldHaveAttendance($date, $attendanceRules);
        
        return ($isHoliday || !$shouldHaveAttendance) ? 'holiday' : 'no_data';
    }

    // ================================================
    // NAVIGATION METHODS
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
        if ($direction === -1) {
            if ($this->currentMonth == 1) {
                $this->currentMonth = 12;
                $this->currentYear--;
            } else {
                $this->currentMonth--;
            }
        } else {
            if ($this->currentMonth == 12) {
                $this->currentMonth = 1;
                $this->currentYear++;
            } else {
                $this->currentMonth++;
            }
        }
        
        $this->logInfo('Month navigation', [
            'direction' => $direction === -1 ? 'previous' : 'next',
            'new_month' => $this->currentMonth,
            'new_year' => $this->currentYear
        ]);
        
        $this->loadAttendanceCalendar();
    }

    // ================================================
    // STATUS PROCESSING METHODS
    // ================================================

    private function extractStatusValue($status): string
    {
        try {
            $this->logInfo('Processing status value', [
                'raw_status' => $status,
                'status_type' => gettype($status),
                'is_enum' => $status instanceof \BackedEnum,
                'class_name' => is_object($status) ? get_class($status) : 'not_object'
            ]);

            if ($status instanceof \BackedEnum) {
                $value = $status->value;
                $this->logInfo('Enum value extracted: ' . $value);
                return $value;
            }

            $stringValue = (string)$status;
            $this->logInfo('String value: ' . $stringValue);
            return $stringValue;
        } catch (\Exception $e) {
            $this->logInfo('Error converting status: ' . $e->getMessage());
            return 'no_data';
        }
    }

    public function getStatusColor(string $status): string
    {
        return self::STATUS_COLORS[$status] ?? self::STATUS_COLORS['no_data'];
    }

    public function getStatusText(string $status): string
    {
        return self::STATUS_TEXTS[$status] ?? 'Tidak Ada Data';
    }

    // ================================================
    // HOLIDAY AND ATTENDANCE RULE UTILITIES
    // ================================================

    private function isHoliday(Carbon $date, Collection $holidays): bool
    {
        return $holidays->contains(function ($holiday) use ($date) {
            return $date->between($holiday->start_date, $holiday->end_date);
        });
    }

    private function shouldHaveAttendance(Carbon $date, Collection $attendanceRules): bool
    {
        $dayName = strtolower($date->format('l'));
        $dateString = $date->format('Y-m-d');
        
        if ($attendanceRules->isEmpty()) {
            return !$date->isWeekend();
        }
        
        // Check for specific date override
        if ($this->hasDateOverrideRule($attendanceRules, $dateString)) {
            return true;
        }
        
        // Check for day of week rules
        return $this->hasDayOfWeekRule($attendanceRules, $dayName);
    }

    private function hasDateOverrideRule(Collection $attendanceRules, string $dateString): bool
    {
        return $attendanceRules->contains(function ($rule) use ($dateString) {
            return $rule->date_override && $rule->date_override->format('Y-m-d') === $dateString;
        });
    }

    private function hasDayOfWeekRule(Collection $attendanceRules, string $dayName): bool
    {
        return $attendanceRules->contains(function ($rule) use ($dayName) {
            return $rule->day_of_week 
                && is_array($rule->day_of_week) 
                && in_array($dayName, $rule->day_of_week);
        });
    }

    // ================================================
    // LOGGING UTILITIES
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
