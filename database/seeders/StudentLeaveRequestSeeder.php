<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\StudentLeaveRequest;
use App\Models\Student;
use Faker\Factory as Faker;

class StudentLeaveRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $students = Student::all();

        foreach ($students as $student) {
            for ($i = 0; $i < 5; $i++) { // Generate 5 leave requests per student
                $startDate = $faker->dateTimeBetween('-3 months', '+3 months');
                StudentLeaveRequest::create([
                    'student_id' => $student->id,
                    'date' => $startDate->format('Y-m-d'),
                    'reason' => $faker->sentence,
                    'submitted_by' => $faker->name,
                    'via' => $faker->randomElement(['form_online', 'manual']),
                ]);
            }
        }
    }
}
