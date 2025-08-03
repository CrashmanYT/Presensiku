<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add unique constraint to student_attendances table
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->unique(['student_id', 'date'], 'unique_student_attendance_per_date');
        });

        // Add unique constraint to teacher_attendances table
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->unique(['teacher_id', 'date'], 'unique_teacher_attendance_per_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->dropUnique('unique_student_attendance_per_date');
        });

        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->dropUnique('unique_teacher_attendance_per_date');
        });
    }
};