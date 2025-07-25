<?php

namespace App\Livewire;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\Attributes\On;

class RealtimeAttendanceDashboard extends Component
{
    public $currentUser = null;
    public $attendanceCalendar = [];
    public $currentMonth;
    public $currentYear;
    public $showDetails = false;
    public $lastChecked;
    public $autoHideTimer;

    protected $listeners = ['attendanceUpdated' => 'refreshData'];

    public function mount()
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
        $this->lastChecked = now();
    }

    public function refreshData()
    {
        \Log::info('RefreshData called at: ' . now());

        // Check for new scans in the last few seconds
        $this->checkForNewScans();

        // This will be called when new attendance is recorded
        if ($this->currentUser) {
            $this->loadAttendanceCalendar();
        }
    }

    public function checkForNewScans()
    {
        // Skip if already showing details
        if ($this->showDetails) {
            \Log::info('Skipping scan check - details already showing');
            return;
        }

        // Look for recent attendance records (last 10 seconds)
        $recentAttendance = StudentAttendance::where('created_at', '>=', now()->subSeconds(10))
            ->with('student')
            ->orderBy('created_at', 'desc')
            ->first();

        \Log::info('Checking for new scans, found: ' . ($recentAttendance ? 'Yes (ID: ' . $recentAttendance->id . ')' : 'No'));

        if ($recentAttendance && $recentAttendance->student) {
            \Log::info('Handling user scanned: fingerprint_id = ' . $recentAttendance->student->fingerprint_id);
            $this->handleUserScanned($recentAttendance->student->fingerprint_id);
        }
    }

    #[On('user-scanned')]
    public function handleUserScanned($fingerprintId)
    {
        \Log::info('handleUserScanned called with fingerprint_id: ' . $fingerprintId);

        // Find user by fingerprint ID
        $user = Student::where('fingerprint_id', $fingerprintId)->first();
        if (!$user) {
            $user = Teacher::where('fingerprint_id', $fingerprintId)->first();
        }

        \Log::info('User found: ' . ($user ? 'Yes (Name: ' . $user->name . ', Type: ' . get_class($user) . ')' : 'No'));

        if ($user) {
            $this->currentUser = $user;
            $this->showDetails = true;
            $this->loadAttendanceCalendar();

            \Log::info('User details set, calendar loaded');

            // Auto hide after 10 seconds
            $this->dispatch('start-auto-hide');
        }
    }

    public function hideDetails()
    {
        $this->showDetails = false;
        $this->currentUser = null;
        $this->attendanceCalendar = [];
    }

    // Method for testing - only available in local environment
    public function testCalendar()
    {
        // Get any student with attendance data for testing
        $student = Student::has('attendances')
            ->whereNotNull('fingerprint_id')
            ->first();
            
        if ($student) {
            \Log::info('Test calendar triggered for student: ' . $student->name . ' (fingerprint_id: ' . $student->fingerprint_id . ')');
            $this->currentUser = $student;
            $this->showDetails = true;
            $this->loadAttendanceCalendar();
        } else {
            \Log::info('No student with attendance found for testing');
        }
    }

    private function loadAttendanceCalendar()
    {
        if (!$this->currentUser) return;

        $startDate = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $endDate = $startDate->copy()->endOfMonth();

        \Log::info('Loading attendance calendar for user: ' . $this->currentUser->name . ' (ID: ' . $this->currentUser->id . ')');
        \Log::info('Date range: ' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'));

        // Get attendance data for the month
        if ($this->currentUser instanceof Student) {
            $attendances = StudentAttendance::where('student_id', $this->currentUser->id)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get()
                ->keyBy(function ($item) {
                    return $item->date->format('Y-m-d');
                });
        } else {
            $attendances = TeacherAttendance::where('teacher_id', $this->currentUser->id)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get()
                ->keyBy(function ($item) {
                    return $item->date->format('Y-m-d');
                });
        }

        \Log::info('Found ' . $attendances->count() . ' attendance records');

        // Debug: Log all attendance records found
        foreach ($attendances as $date => $attendance) {
            $statusValue = $this->getStatusValue($attendance->status);
            \Log::info('Attendance record - Date: ' . $date . ', Status: ' . $statusValue);
        }

        // Build calendar array
        $calendar = [];
        $currentDate = $startDate->copy();

        while ($currentDate->month == $this->currentMonth) {
            $dateString = $currentDate->format('Y-m-d');
            $attendance = $attendances->get($dateString);

            // Get status with better error handling
            $status = 'no_data';
            if ($attendance) {
                $status = $this->getStatusValue($attendance->status);
            }

            $calendar[] = [
                'date' => $currentDate->day,
                'full_date' => $dateString,
                'status' => $status,
                'is_today' => $currentDate->isToday(),
                'is_weekend' => $currentDate->isWeekend(),
            ];

            \Log::info('Calendar day ' . $currentDate->day . ' (' . $dateString . '): status = ' . $status);

            $currentDate->addDay();
        }

        $this->attendanceCalendar = $calendar;
        \Log::info('Calendar built with ' . count($calendar) . ' days');
    }

    public function previousMonth()
    {
        if ($this->currentMonth == 1) {
            $this->currentMonth = 12;
            $this->currentYear--;
        } else {
            $this->currentMonth--;
        }
        $this->loadAttendanceCalendar();
    }

    public function nextMonth()
    {
        if ($this->currentMonth == 12) {
            $this->currentMonth = 1;
            $this->currentYear++;
        } else {
            $this->currentMonth++;
        }
        $this->loadAttendanceCalendar();
    }

    private function getStatusValue($status)
    {
        try {
            \Log::info('Processing status value', [
                'raw_status' => $status,
                'status_type' => gettype($status),
                'is_enum' => $status instanceof \BackedEnum,
                'class_name' => is_object($status) ? get_class($status) : 'not_object'
            ]);

            if ($status instanceof \BackedEnum) {
                $value = $status->value;
                \Log::info('Enum value extracted: ' . $value);
                return $value;
            }

            $stringValue = (string)$status;
            \Log::info('String value: ' . $stringValue);
            return $stringValue;
        } catch (\Exception $e) {
            \Log::error('Error converting status: ' . $e->getMessage());
            return 'no_data';
        }
    }

    public function getStatusColor($status)
    {
        \Log::info('Getting color for status: ' . $status);

        return match($status) {
            'hadir' => 'bg-green-500',
            'tidak_hadir' => 'bg-red-500',
            'terlambat' => 'bg-yellow-500',
            'izin', 'sakit' => 'bg-blue-500',
            'no_data' => 'bg-gray-200',
            'error' => 'bg-gray-300',
            default => 'bg-gray-200'
        };
    }

    public function getStatusText($status)
    {
        return match($status) {
            'hadir' => 'Hadir',
            'tidak_hadir' => 'Tidak Hadir',
            'terlambat' => 'Terlambat',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            default => 'Tidak Ada Data'
        };
    }

    public function render()
    {
        return view('livewire.realtime-attendance-dashboard', [
            'monthName' => Carbon::create($this->currentYear, $this->currentMonth, 1)->format('F Y')
        ])->layout('layouts.dashboard-standalone');
    }
}
