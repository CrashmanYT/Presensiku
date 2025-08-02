<?php

namespace Database\Factories;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * The name of the corresponding model.
     *
     * @var string
     */
    protected $model = Teacher::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'nip' => $this->faker->unique()->numerify('##############'),
            'fingerprint_id' => $this->faker->unique()->numerify('####'),
            'photo' => 'https://i.pravatar.cc/150?img='.$this->faker->numberBetween(1, 70),
            'whatsapp_number' => $this->faker->phoneNumber(),
        ];
    }
}
