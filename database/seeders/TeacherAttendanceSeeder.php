<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TeacherAttendance;
use App\Models\Teacher;
use App\Models\Device;
use Faker\Factory as Faker;

class TeacherAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $teachers = Teacher::all();
        $devices = Device::all();

        foreach ($teachers as $teacher) {
            for ($i = 0; $i < 30; $i++) { // Generate 30 attendance records for each teacher
                $date = $faker->dateTimeBetween('-1 month', 'now');
                $timeIn = $faker->time('H:i:s', '07:00:00');
                $timeOut = $faker->time('H:i:s', '15:00:00');
                $status = $faker->randomElement(['hadir', 'terlambat', 'tidak_hadir', 'izin', 'sakit']);

                TeacherAttendance::create([
                    'teacher_id' => $teacher->id,
                    'date' => $date->format('Y-m-d'),
                    'time_in' => $timeIn,
                    'time_out' => $timeOut,
                    'status' => $status,
                    'photo_in' => ($status == 'hadir' || $status == 'terlambat') ? 'https://i.pravatar.cc/150?img=' . $faker->numberBetween(1, 70) : null,
                    'device_id' => $faker->randomElement($devices->pluck('id')),
                ]);
            }
        }
    }
}
