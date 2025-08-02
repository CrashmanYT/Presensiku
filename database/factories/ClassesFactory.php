<?php

namespace Database\Factories;

use App\Models\Classes;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Classes>
 */
class ClassesFactory extends Factory
{
    /**
     * The name of the corresponding model.
     *
     * @var string
     */
    protected $model = Classes::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word() . ' Class',
            'level' => $this->faker->randomElement(['X', 'XI', 'XII']),
            'major' => $this->faker->randomElement(['IPA', 'IPS', 'Bahasa']),
            'homeroom_teacher_nip' => Teacher::factory()->create()->nip,
        ];
    }
}
