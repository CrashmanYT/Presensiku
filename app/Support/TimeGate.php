<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class TimeGate
{
    /**
     * Returns true if $now is within ±$toleranceMinutes of HH:MM (local timezone),
     * or if $force is true.
     */
    public function shouldRunNow(
        CarbonInterface $now,
        string $timeHHmm,
        bool $force = false,
        int $toleranceMinutes = 1
    ): bool {
        if ($force) {
            return true;
        }

        // Parse HH:MM and build candidates for yesterday, today, tomorrow
        [$H, $m] = explode(':', $timeHHmm, 2);
        $tz = $now->getTimezone();

        $yesterday = $now->copy()->subDay();
        $tomorrow  = $now->copy()->addDay();

        $candidates = [
            Carbon::create($now->year, $now->month, $now->day, (int) $H, (int) $m, 0, $tz),
            Carbon::create($yesterday->year, $yesterday->month, $yesterday->day, (int) $H, (int) $m, 0, $tz),
            Carbon::create($tomorrow->year, $tomorrow->month, $tomorrow->day, (int) $H, (int) $m, 0, $tz),
        ];

        $minDiff = min(array_map(static fn ($t) => $now->diffInMinutes($t), $candidates));
        return $minDiff <= $toleranceMinutes;
    }

    /**
     * Returns true if $now is within ±$toleranceMinutes of a specific target datetime.
     */
    public function isWithinWindow(
        CarbonInterface $now,
        CarbonInterface $target,
        bool $force = false,
        int $toleranceMinutes = 1
    ): bool {
        if ($force) {
            return true;
        }
        return abs($now->diffInMinutes($target)) <= $toleranceMinutes;
    }

    /**
     * Helper for consistent logging/messages about the allowed window.
     */
    public function windowMessage(string $timeHHmm, int $toleranceMinutes = 1): string
    {
        return "Allowed window: {$timeHHmm} ±{$toleranceMinutes} minute(s)";
    }
}
