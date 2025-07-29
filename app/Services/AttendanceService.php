<?php

namespace App\Services;

use App\Enums\AttendanceStatusEnum;
use App\Helpers\SettingsHelper;
use App\Models\AttendanceRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceService
{


    protected SettingsHelper $settingsHelper;
    public function __construct(SettingsHelper $settingsHelper)
    {
        $this->settingsHelper = $settingsHelper;
    }
    /**
     * Get attendance rule for class and date
     */
    public function getAttendanceRule(int | null $classId, Carbon $scanDateTime): ?AttendanceRule
    {
        $scanDate = $scanDateTime->format('Y-m-d');
        $dayName = strtolower($scanDateTime->format('l'));

        if ($classId) {
            Log::info('Getting attendance rule', [
                'class_id' => $classId,
                'scan_date' => $scanDate,
                'day_name' => $dayName
            ]);
        }

        // Priority 1: Check for specific date override
        $dateOverrideRule = $this->getDateOverrideRule($classId, $scanDate);
        if ($dateOverrideRule) {
            return $dateOverrideRule;
        }

        // Priority 2: Check for day of week rule
        $dayOfWeekRule = $this->getDayOfWeekRule($classId, $dayName);
        if ($dayOfWeekRule) {
            return $dayOfWeekRule;
        }

        // Fallback: Get any rule for this class
        return $this->createDefaultRuleFromSettings();
    }

    private function createDefaultRuleFromSettings(): AttendanceRule
    {
        $defaultSettings = SettingsHelper::getAttendanceSettings();

        return new AttendanceRule([
            'time_in_start' => $defaultSettings['time_in_start'] ?? "07:00:00",
            'time_in_end' => $defaultSettings['time_in_end'] ?? "08:00:00",
            'time_out_start' => $defaultSettings['time_out_start'] ?? "14:00:00",
            'time_out_end' => $defaultSettings['time_out_end'] ?? "16:00:00",
            "description" => "Jadwal Absensi Bawaan",
            "day_of_week" => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
        ]);
    }

    /**
     * Check attendance status based on scan time and rule
     */
    public function checkAttendanceStatus(Carbon $scanTime, AttendanceRule $attendanceRule): AttendanceStatusEnum
    {
        $scanDate = $scanTime->toDateString();
        $dayName = strtolower($scanTime->format('l'));
        $scanTimeOnly = $scanTime->format('H:i:s');

        $timeInStart = Carbon::parse($attendanceRule->time_in_start)->format('H:i:s');
        $timeInEnd = Carbon::parse($attendanceRule->time_in_end)->format('H:i:s');

        Log::info('Checking attendance status', [
            'scan_date' => $scanDate,
            'scan_time' => $scanTimeOnly,
            'day_name' => $dayName,
            'time_in_start' => $timeInStart,
            'time_in_end' => $timeInEnd,
            'date_override' => $attendanceRule->date_override,
            'day_of_week' => $attendanceRule->day_of_week
        ]);

        // Check if there's a specific date override
        if ($this->isDateOverrideRule($attendanceRule, $scanDate)) {
            return $this->determineStatusByTimeRange($scanTimeOnly, $timeInStart, $timeInEnd);
        }

        // Check if current day is in the allowed days of week
        if ($this->isDayOfWeekRule($attendanceRule, $dayName)) {
            return $this->determineStatusByTimeRange($scanTimeOnly, $timeInStart, $timeInEnd);
        }

        Log::info('No matching rule found, defaulting to late');
        return AttendanceStatusEnum::TERLAMBAT;
    }

    private function getDateOverrideRule(int | null $classId, string $scanDate): ?AttendanceRule
    {
        $rule = AttendanceRule::where('class_id', $classId)
            ->whereDate('date_override', $scanDate)
            ->first();

        if ($rule) {
            Log::info('Found date override rule', [
                'rule_id' => $rule->id,
                'date_override' => $rule->date_override->format('Y-m-d'),
                'time_in_start' => $rule->time_in_start,
                'time_in_end' => $rule->time_in_end
            ]);
        }

        return $rule;
    }

    private function getDayOfWeekRule(int | null $classId, string $dayName): ?AttendanceRule
    {
        $rule = AttendanceRule::where('class_id', $classId)
            ->whereNull('date_override')
            ->where(function ($query) use ($dayName) {
                $query->where('day_of_week', 'like', '%"' . $dayName . '"%');
            })
            ->first();

        if ($rule) {
            Log::info('Found day of week rule', [
                'rule_id' => $rule->id,
                'day_of_week' => $rule->day_of_week,
                'time_in_start' => $rule->time_in_start,
                'time_in_end' => $rule->time_in_end
            ]);
        }

        return $rule;
    }

    private function getFallbackRule(int $classId): ?AttendanceRule
    {
        $rule = AttendanceRule::where('class_id', $classId)->first();

        if ($rule) {
            Log::warning('Using fallback rule', [
                'rule_id' => $rule->id,
                'class_id' => $classId
            ]);
        } else {
            Log::error('No attendance rule found for class', ['class_id' => $classId]);
        }

        return $rule;
    }

    private function isDateOverrideRule(AttendanceRule $attendanceRule, string $scanDate): bool
    {
        return $attendanceRule->date_override && $attendanceRule->date_override->format('Y-m-d') === $scanDate;
    }

    private function isDayOfWeekRule(AttendanceRule $attendanceRule, string $dayName): bool
    {
        return $attendanceRule->day_of_week && in_array($dayName, $attendanceRule->day_of_week);
    }

    private function determineStatusByTimeRange(string $scanTime, string $timeStart, string $timeEnd): AttendanceStatusEnum
    {
        if ($scanTime >= $timeStart && $scanTime <= $timeEnd) {
            return AttendanceStatusEnum::HADIR;
        }

        return AttendanceStatusEnum::TERLAMBAT;
    }
}
