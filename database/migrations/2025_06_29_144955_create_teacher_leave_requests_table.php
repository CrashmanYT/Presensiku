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
        Schema::create('teacher_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->enum('type', ['sakit', 'izin'])->default('izin');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $table->string('attachment')->nullable();
            $table->string('submitted_by')->nullable();
            $table->enum('via', ['manual', 'form_online']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_leave_requests');
    }
};
