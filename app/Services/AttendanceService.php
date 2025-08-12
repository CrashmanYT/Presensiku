<?php

namespace App\Services;

use App\Enums\AttendanceStatusEnum;
use App\Helpers\SettingsHelper;
use App\Models\AttendanceRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for resolving attendance rules and deriving attendance status
 * based on scan timestamps and configured schedules.
 *
 * This service prioritizes rule resolution in the following order:
 * 1) Specific date overrides
 * 2) Day-of-week rules
 * 3) Default settings fallback
 */
class AttendanceService
{
    /**
     * Application settings helper used to retrieve default attendance settings
     * when no explicit rule is found.
     */
    protected SettingsHelper $settingsHelper;

    /**
     * Create a new service instance.
     *
     * @param SettingsHelper $settingsHelper Helper to read configured defaults
     */
    public function __construct(SettingsHelper $settingsHelper)
    {
        $this->settingsHelper = $settingsHelper;
    }

    /**
     * Resolve the effective attendance rule for a given class and scan datetime.
     *
     * Resolution order:
     * - Date override rule for the exact date
     * - Day-of-week rule matching the scan day
     * - Default fallback derived from settings
     *
     * @param int|null $classId Class identifier or null (e.g., for teachers)
     * @param Carbon $scanDateTime The timestamp of the scan
     * @return AttendanceRule|null A concrete rule instance; never null in current implementation
     */
    public function getAttendanceRule(?int $classId, Carbon $scanDateTime): ?AttendanceRule
    {
        $scanDate = $scanDateTime->format('Y-m-d');
        $dayName = strtolower($scanDateTime->format('l'));

        if ($classId) {
            Log::info('Getting attendance rule', [
                'class_id' => $classId,
                'scan_date' => $scanDate,
                'day_name' => $dayName,
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

    /**
     * Build a default rule object using application attendance settings.
     *
     * Missing settings are safely defaulted to reasonable values.
     *
     * @return AttendanceRule Non-persisted rule instance constructed from settings
     */
    private function createDefaultRuleFromSettings(): AttendanceRule
    {
        $defaultSettings = $this->settingsHelper->getAttendanceSettings();

        return new AttendanceRule([
            'time_in_start' => $defaultSettings['time_in_start'] ?? '07:00:00',
            'time_in_end' => $defaultSettings['time_in_end'] ?? '08:00:00',
            'time_out_start' => $defaultSettings['time_out_start'] ?? '14:00:00',
            'time_out_end' => $defaultSettings['time_out_end'] ?? '16:00:00',
            'description' => 'Jadwal Absensi Bawaan',
            'day_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
        ]);
    }

    /**
     * Derive attendance status based on a scan time and an effective rule.
     *
     * If a date override exists for the scan date or the scan day matches the
     * rule's allowed days, the status is determined against the configured time
     * window. Otherwise the status defaults to late.
     *
     * @param Carbon $scanTime Timestamp of the scan
     * @param AttendanceRule $attendanceRule Effective rule to evaluate against
     * @return AttendanceStatusEnum Resulting status (e.g., HADIR or TERLAMBAT)
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
            'day_of_week' => $attendanceRule->day_of_week,
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

    /**
     * Find a rule that specifically overrides the given date.
     *
     * @param int|null $classId Target class id, if any
     * @param string $scanDate Date in Y-m-d format
     * @return AttendanceRule|null Matching rule if found
     */
    private function getDateOverrideRule(?int $classId, string $scanDate): ?AttendanceRule
    {
        return AttendanceRule::where('class_id', $classId)
            ->whereDate('date_override', '=' , $scanDate)
            ->first();
    }

    /**
     * Find a rule that applies to a specific day of the week.
     *
     * @param int|null $classId Target class id, if any
     * @param string $dayName Lowercase day name (e.g., 'monday')
     * @return AttendanceRule|null Matching rule if found
     */
    private function getDayOfWeekRule(?int $classId, string $dayName): ?AttendanceRule
    {
        return AttendanceRule::where('class_id', $classId)
            ->whereNull('date_override')
            ->whereJsonContains('day_of_week', $dayName)
            ->first();
    }

    /**
     * Determine if the provided rule is a date-override rule for the scan date.
     *
     * @param AttendanceRule $attendanceRule Rule to inspect
     * @param string $scanDate Date in Y-m-d format
     * @return bool True if the rule overrides the provided date
     */
    private function isDateOverrideRule(AttendanceRule $attendanceRule, string $scanDate): bool
    {
        return $attendanceRule->date_override && $attendanceRule->date_override->format('Y-m-d') === $scanDate;
    }

    /**
     * Determine if the provided rule applies to the given day of the week.
     *
     * @param AttendanceRule $attendanceRule Rule to inspect
     * @param string $dayName Lowercase day name (e.g., 'monday')
     * @return bool True if the rule includes the given day
     */
    private function isDayOfWeekRule(AttendanceRule $attendanceRule, string $dayName): bool
    {
        return $attendanceRule->day_of_week && in_array($dayName, $attendanceRule->day_of_week);
    }

    /**
     * Determine attendance status by comparing scan time against a time window.
     *
     * @param string $scanTime HH:ii:ss scan time
     * @param string $timeStart HH:ii:ss start of valid window
     * @param string $timeEnd HH:ii:ss end of valid window
     * @return AttendanceStatusEnum HADIR if within window; otherwise TERLAMBAT
     */
    private function determineStatusByTimeRange(string $scanTime, string $timeStart, string $timeEnd): AttendanceStatusEnum
    {
        if ($scanTime >= $timeStart && $scanTime <= $timeEnd) {
            return AttendanceStatusEnum::HADIR;
        }

        return AttendanceStatusEnum::TERLAMBAT;
    }
}
