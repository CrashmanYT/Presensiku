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
        $teacherIds = Teacher::pluck('id')->toArray();
        if (empty($teacherIds)) {
            throw new \Exception('TeacherSeeder needs to be run before TeacherLeaveRequestSeeder.');
        }

        return [
            'teacher_id' => $this->faker->randomElement($teacherIds),
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
