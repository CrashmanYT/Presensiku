<?php

namespace Database\Factories;

use App\Enums\DayOfWeekEnum;
use App\Models\AttendanceRule;
use App\Models\Classes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceRule>
 */
class AttendanceRuleFactory extends Factory
{
    /**
     * The name of the corresponding model.
     *
     * @var string
     */
    protected $model = AttendanceRule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_id' => Classes::factory(),
            'day_of_week' => $this->faker->randomElements(DayOfWeekEnum::cases(), $this->faker->numberBetween(1, 5)),
            'date_override' => $this->faker->boolean(20) ? $this->faker->date() : null,
            'time_in_start' => $this->faker->time('H:i:s', '07:00:00'),
            'time_in_end' => $this->faker->time('H:i:s', '08:00:00'),
            'time_out_start' => $this->faker->time('H:i:s', '15:00:00'),
            'time_out_end' => $this->faker->time('H:i:s', '16:00:00'),
            'description' => $this->faker->sentence(),
        ];
    }
}
