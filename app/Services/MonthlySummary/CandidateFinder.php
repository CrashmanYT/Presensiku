<?php

namespace App\Services\MonthlySummary;

use App\Models\DisciplineRanking;
use Illuminate\Support\Collection;

class CandidateFinder
{
    /**
     * Return [Collection $selected, int $extraCount]
     */
    public function findCandidates(string $monthKey, array $thresholds, int $limit): array
    {
        $minLate = (int) ($thresholds['min_total_late'] ?? PHP_INT_MAX);
        $minAbsent = (int) ($thresholds['min_total_absent'] ?? PHP_INT_MAX);
        $minScore = (int) ($thresholds['min_score'] ?? 0);

        $query = DisciplineRanking::with(['student.class'])
            ->where('month', $monthKey)
            ->where(function ($q) use ($minLate, $minAbsent, $minScore) {
                $q->where('total_late', '>=', $minLate)
                    ->orWhere('total_absent', '>=', $minAbsent)
                    ->orWhere('score', '<=', $minScore);
            })
            ->orderBy('score', 'asc')
            ->orderBy('total_absent', 'desc')
            ->orderBy('total_late', 'desc');

        $rankings = $query->get();
        $selected = $rankings->take($limit)->values();
        $extraCount = $rankings->count() - $selected->count();
        return [$selected, $extraCount];
    }
}
