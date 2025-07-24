<?php

namespace Database\Factories;

use App\Enums\AttendanceStatusEnum;
use App\Models\Device;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeacherAttendance>
 */
class TeacherAttendanceFactory extends Factory
{
    /**
     * The name of the corresponding model.
     *
     * @var string
     */
    protected $model = TeacherAttendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(AttendanceStatusEnum::cases());
        $timeIn = $this->faker->time('H:i:s', '07:00:00');
        $timeOut = $this->faker->time('H:i:s', '15:00:00');

        return [
            'teacher_id' => Teacher::factory(),
            'date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'status' => $status,
            'photo_in' => ($status === AttendanceStatusEnum::Present || $status === AttendanceStatusEnum::Late) ? 'https://i.pravatar.cc/150?img=' . $this->faker->numberBetween(1, 70) : null,
            'device_id' => Device::factory(),
        ];
    }
}