<?php

namespace App\Services\MonthlySummary;

use App\Models\DisciplineRanking;
use Illuminate\Support\Collection;

/**
 * Encapsulates the query to find discipline ranking candidates
 * for a given month using configured thresholds and a limit.
 */
class CandidateFinder
{
    /**
     * Find discipline candidates for the given month.
     *
     * The query returns all matches ordered by severity, then selects up to
     * the provided limit. The remainder count is returned as extraCount.
     *
     * @param string              $monthKey    Target month in format YYYY-MM
     * @param array<string,int>   $thresholds  Thresholds: min_total_late, min_total_absent, min_score
     * @param int                 $limit       Maximum number of candidates to include
     * @return array{0: Collection, 1: int}    [selected, extraCount]
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
