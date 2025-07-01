<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TeacherLeaveRequest;
use App\Models\Teacher;
use Faker\Factory as Faker;

class TeacherLeaveRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $teachers = Teacher::all();

        foreach ($teachers as $teacher) {
            for ($i = 0; $i < 3; $i++) { // Generate 3 leave requests per teacher
                $startDate = $faker->dateTimeBetween('-3 months', '+3 months');
                TeacherLeaveRequest::create([
                    'teacher_id' => $teacher->id,
                    'date' => $startDate->format('Y-m-d'),
                    'reason' => $faker->sentence,
                    'submitted_by' => $faker->name,
                    'via' => $faker->randomElement(['manual', 'form_online']),
                ]);
            }
        }
    }
}
