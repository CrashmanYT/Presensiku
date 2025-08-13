<?php

namespace Tests\Unit\MonthlySummary;

use App\Models\Classes;
use App\Models\DisciplineRanking;
use App\Models\Student;
use App\Services\MonthlySummary\CandidateFinder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CandidateFinderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_selects_candidates_by_thresholds_and_reports_extra_count(): void
    {
        $class = Classes::factory()->create();
        $students = Student::factory(3)->create(['class_id' => $class->id]);

        $monthKey = now()->format('Y-m');

        // 3 candidates, all meeting at least one OR threshold
        DisciplineRanking::create([
            'student_id' => $students[0]->id,
            'month' => $monthKey,
            'total_present' => 10,
            'total_absent' => 3,  // meets absent threshold
            'total_late' => 1,
            'score' => -3,
        ]);
        DisciplineRanking::create([
            'student_id' => $students[1]->id,
            'month' => $monthKey,
            'total_present' => 12,
            'total_absent' => 1,
            'total_late' => 4,  // meets late threshold
            'score' => -2,
        ]);
        DisciplineRanking::create([
            'student_id' => $students[2]->id,
            'month' => $monthKey,
            'total_present' => 8,
            'total_absent' => 0,
            'total_late' => 0,
            'score' => -10, // meets score threshold
        ]);

        $finder = new CandidateFinder();
        [$selected, $extra] = $finder->findCandidates($monthKey, [
            'min_total_late' => 3,
            'min_total_absent' => 2,
            'min_score' => -5,
        ], limit: 2);

        $this->assertCount(2, $selected);
        $this->assertSame(1, $extra);

        // Ordered by most severe score first (asc), then absent desc, then late desc
        // So the first item's score should be <= the second item's score
        $this->assertLessThanOrEqual($selected[1]->score, $selected[0]->score);
    }
}
