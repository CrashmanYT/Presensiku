<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

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

                TeacherAttendance::updateOrInsert([
                    'teacher_id' => $teacher->id,
                    'date' => $date->format('Y-m-d'),
                ],[
                    'teacher_id' => $teacher->id,
                    'date' => $date->format('Y-m-d'),
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
