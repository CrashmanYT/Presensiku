<?php

namespace Database\Factories;

use App\Models\DisciplineRanking;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DisciplineRanking>
 */
class DisciplineRankingFactory extends Factory
{
    /**
     * The name of the corresponding model.
     *
     * @var string
     */
    protected $model = DisciplineRanking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'month' => $this->faker->monthName(),
            'total_present' => $this->faker->numberBetween(15, 25),
            'total_absent' => $this->faker->numberBetween(0, 5),
            'total_late' => $this->faker->numberBetween(0, 10),
            'score' => $this->faker->numberBetween(70, 100),
        ];
    }
}