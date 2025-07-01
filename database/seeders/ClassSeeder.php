<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Classes;
use App\Models\Teacher;
use Faker\Factory as Faker;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $teachers = Teacher::all();

        $levels = ['X', 'XI', 'XII'];
        $majors = ['IPA', 'IPS', 'Bahasa', 'Teknik Komputer Jaringan', 'Multimedia'];

        foreach ($levels as $level) {
            foreach ($majors as $major) {
                for ($i = 1; $i <= 3; $i++) { // Create 3 classes for each level and major combination
                    Classes::create([
                        'name' => $level . ' ' . $major . ' ' . $i,
                        'level' => $level,
                        'major' => $major,
                        'homeroom_teacher_id' => $faker->randomElement($teachers->pluck('id')),
                    ]);
                }
            }
        }
    }
}
