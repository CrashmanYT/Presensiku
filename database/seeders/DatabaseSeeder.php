<?php

namespace Database\Seeders;

use App\Models\User;
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
            'password' => 'password',
        ]);

        $this->call([
            TeacherSeeder::class,
            ClassSeeder::class,
            StudentSeeder::class,
            SettingsSeeder::class,
            DeviceSeeder::class,
            AttendanceRuleSeeder::class,
            StudentAttendanceSeeder::class,
            TeacherAttendanceSeeder::class,
            StudentLeaveRequestSeeder::class,
            TeacherLeaveRequestSeeder::class,
            //            HolidaySeeder::class,
        ]);
    }
}
