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
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint_id');
            $table->enum('event_type', ['scan_in', 'scan_out']);
            $table->timestamp('scanned_at');
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->enum('result', ['success', 'fail']);
            $table->timestamps();

            $table->index('fingerprint_id');
            $table->index('scanned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_logs');
    }
};
