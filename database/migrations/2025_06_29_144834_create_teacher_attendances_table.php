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
        Schema::create('teacher_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->date('date');
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->enum('status', ['hadir', 'terlambat', 'tidak_hadir', 'izin', 'sakit']);
            $table->string('photo_in')->nullable();
            $table->foreignId('device_id')->nullable()->constrained('devices')->onDelete('set null');
            $table->timestamps();

            $table->index(['teacher_id', 'date']);
            $table->unique(['teacher_id', 'date']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_attendances');
    }
};
