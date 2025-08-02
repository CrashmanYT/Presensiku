<?php

namespace Database\Seeders;

use App\Models\StudentLeaveRequest;
use Illuminate\Database\Seeder;

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
