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
        Schema::table('settings', function (Blueprint $table) {
            $table->enum('type', ['string', 'integer', 'boolean', 'json', 'float'])->default('string')->after('value');
            $table->string('group_name', 100)->nullable()->after('type');
            $table->text('description')->nullable()->after('group_name');
            $table->boolean('is_public')->default(false)->after('description');
            $table->unsignedBigInteger('created_by')->nullable()->after('is_public');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['type', 'group_name', 'description', 'is_public', 'created_by', 'updated_by']);
        });
    }
};
