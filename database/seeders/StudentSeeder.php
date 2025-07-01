<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Classes;
use Faker\Factory as Faker;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $classes = Classes::all();

        foreach ($classes as $class) {
            for ($i = 0; $i < 30; $i++) { // Generate 30 students for each class
                Student::create([
                    'name' => $faker->name,
                    'nis' => $faker->unique()->numerify('##########'), // 10 digit NIS
                    'class_id' => $class->id,
                    'gender' => $faker->randomElement(['L', 'P']),
                    'fingerprint_id' => $faker->unique()->numerify('########'), // 8 digit fingerprint ID
                    'photo' => 'https://i.pravatar.cc/150?img=' . $faker->numberBetween(1, 70),
                    'parent_whatsapp' => '08' . $faker->unique()->numerify('##########'), // Indonesian WhatsApp number format
                ]);
            }
        }
    }
}
