<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Student;
use App\Models\StudentAttendance;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class StudentAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $students = Student::all();
        $devices = Device::all();

        foreach ($students as $student) {
            for ($i = 0; $i < 10; $i++) { // Generate 30 attendance records for each student
                $date = $faker->dateTimeBetween('-1 month', 'now');
                $timeIn = $faker->time('H:i:s', '08:00:00');
                $timeOut = $faker->time('H:i:s', '16:00:00');
                $status = $faker->randomElement(['hadir', 'terlambat', 'tidak_hadir', 'izin', 'sakit']);

                StudentAttendance::updateOrInsert([
                    'student_id' => $student->id,
                    'date' => $date->format('Y-m-d'),
                ],[
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'status' => $status,
                    'photo_in' => ($status == 'hadir' || $status == 'terlambat') ? 'https://i.pravatar.cc/150?img='.$faker->numberBetween(1, 70) : null,
                    'device_id' => $faker->randomElement($devices->pluck('id')),
                ]);
            }
        }
    }
}
