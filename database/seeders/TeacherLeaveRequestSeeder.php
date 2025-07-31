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
        TeacherLeaveRequest::factory()->count(50)->create();
    }
}
