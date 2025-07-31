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
        StudentLeaveRequest::factory()->count(50)->create();
    }
}
