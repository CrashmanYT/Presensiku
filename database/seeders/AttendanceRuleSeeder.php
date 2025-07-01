<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AttendanceRule;
use App\Models\Classes;
use Faker\Factory as Faker;

class AttendanceRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $classes = Classes::all();

        foreach ($classes as $class) {
            // Rule for weekdays (Monday-Friday)
            AttendanceRule::create([
                'class_id' => $class->id,
                'day_of_week' => json_encode(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']),
                'date_override' => null,
                'time_in_start' => '07:00:00',
                'time_in_end' => '07:30:00',
                'time_out_start' => '14:00:00',
                'time_out_end' => '16:00:00',
                'description' => 'Aturan kehadiran reguler untuk hari kerja',
            ]);

            // Optional: Add a specific rule for a holiday or special event
            AttendanceRule::create([
                'class_id' => $class->id,
                'day_of_week' => null,
                'date_override' => $faker->dateTimeBetween('+1 month', '+3 months')->format('Y-m-d'),
                'time_in_start' => '08:00:00',
                'time_in_end' => '09:00:00',
                'time_out_start' => '12:00:00',
                'time_out_end' => '13:00:00',
                'description' => 'Aturan khusus untuk acara tertentu',
            ]);
        }
    }
}
