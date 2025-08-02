<?php

namespace Database\Seeders;

use App\Models\TeacherLeaveRequest;
use Illuminate\Database\Seeder;

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
