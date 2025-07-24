<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'password'
        ]);

        $this->call([
            TeacherSeeder::class,
//            DeviceSeeder::class,
            ClassSeeder::class,
            StudentSeeder::class,
            AttendanceRuleSeeder::class,
            StudentAttendanceSeeder::class,
            TeacherAttendanceSeeder::class,
            StudentLeaveRequestSeeder::class,
            TeacherLeaveRequestSeeder::class,
            HolidaySeeder::class,
        ]);
    }
}
