<?php

namespace Database\Factories;

use App\Enums\AttendanceStatusEnum;
use App\Models\Device;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentAttendance>
 */
class StudentAttendanceFactory extends Factory
{
    /**
     * The name of the corresponding model.
     *
     * @var string
     */
    protected $model = StudentAttendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(AttendanceStatusEnum::cases());
        $timeIn = $this->faker->time('H:i:s', '08:00:00');
        $timeOut = $this->faker->time('H:i:s', '16:00:00');

        // Get a random existing device ID
        $deviceIds = Device::pluck('id')->toArray();

        // If no devices exist, this indicates a problem with seeder order or DeviceSeeder itself.
        // Throw an exception to make this explicit during development.
        if (empty($deviceIds)) {
            throw new \Exception('No devices found in the database. Ensure DeviceSeeder runs before StudentAttendanceSeeder.');
        }

        $randomDeviceId = $this->faker->randomElement($deviceIds);

        return [
            'student_id' => Student::factory(),
            'date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'status' => $status,
            'photo_in' => ($status === AttendanceStatusEnum::HADIR || $status === AttendanceStatusEnum::TERLAMBAT) ? 'https://i.pravatar.cc/150?img=' . $this->faker->numberBetween(1, 70) : null,
            'device_id' => $randomDeviceId,
        ];
    }
}
