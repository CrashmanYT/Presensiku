<?php

namespace Database\Factories;

use App\Enums\LeaveRequestViaEnum;
use App\Models\Teacher;
use App\Models\TeacherLeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeacherLeaveRequest>
 */
class TeacherLeaveRequestFactory extends Factory
{
    /**
     * The name of the corresponding model.
     *
     * @var string
     */
    protected $model = TeacherLeaveRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => Teacher::factory(),
            'date' => $this->faker->date(),
            'reason' => $this->faker->sentence(),
            'submitted_by' => $this->faker->name(),
            'via' => $this->faker->randomElement(LeaveRequestViaEnum::cases()),
        ];
    }
}