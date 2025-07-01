<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Teacher;
use Faker\Factory as Faker;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        for ($i = 0; $i < 20; $i++) { // Generate 20 dummy teachers
            Teacher::create([
                'name' => $faker->name,
                'nip' => $faker->unique()->numerify('##################'), // 18 digit NIP
                'fingerprint_id' => $faker->unique()->numerify('########'), // 8 digit fingerprint ID
                'photo' => 'https://i.pravatar.cc/150?img=' . $faker->numberBetween(1, 70),
                'whatsapp_number' => '08' . $faker->unique()->numerify('##########'), // Indonesian WhatsApp number format
            ]);
        }
    }
}
