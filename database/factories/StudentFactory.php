<?php

namespace Database\Factories;

use App\Enums\GenderEnum;
use App\Models\Classes;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * The name of the corresponding model.
     *
     * @var string
     */
    protected $model = Student::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'nis' => $this->faker->unique()->numerify('##########'),
            'class_id' => Classes::factory(),
            'gender' => $this->faker->randomElement(GenderEnum::cases()),
            'fingerprint_id' => $this->faker->unique()->numerify('####'),
            'photo' => 'https://i.pravatar.cc/150?img='.$this->faker->numberBetween(1, 70),
            'parent_whatsapp' => $this->faker->phoneNumber(),
        ];
    }
}
