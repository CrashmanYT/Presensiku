<?php

namespace Database\Factories;

use App\Enums\LeaveRequestViaEnum;
use App\Models\Student;
use App\Models\StudentLeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentLeaveRequest>
 */
class StudentLeaveRequestFactory extends Factory
{
    /**
     * The name of the corresponding model.
     *
     * @var string
     */
    protected $model = StudentLeaveRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'date' => $this->faker->date(),
            'reason' => $this->faker->sentence(),
            'submitted_by' => $this->faker->name(),
            'via' => $this->faker->randomElement(LeaveRequestViaEnum::cases()),
        ];
    }
}