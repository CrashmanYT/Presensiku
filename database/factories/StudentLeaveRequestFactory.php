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
     * @throws \Exception
     */
    public function definition(): array
    {
        $studentIds = Student::pluck('id')->toArray();
        if (empty($studentIds)) {
            throw new \Exception('TeacherSeeder needs to be run before TeacherLeaveRequestSeeder.');
        }


        return [
            'student_id' => $this->faker->randomElement($studentIds),
            'type' => $this->faker->randomElement(['sakit', 'izin']),
            'start_date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'end_date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'reason' => $this->faker->sentence(),
            'submitted_by' => $this->faker->name(),
            'attachment' => $this->faker->imageUrl(),
            'via' => $this->faker->randomElement(LeaveRequestViaEnum::cases()),
        ];
    }
}
