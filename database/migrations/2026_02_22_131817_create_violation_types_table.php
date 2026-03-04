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
        Schema::create('violation_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('group', 100);
            $table->enum('impact_level', ['rendah', 'sedang', 'tinggi', 'sangat tinggi', 'fatal']);
            $table->string('description', 255);
            $table->unsignedInteger('points')->default(0);
            $table->enum('status', ['aktif', 'non aktif'])->default('aktif');
            $table->timestamps();

            // Indexes
            $table->index('group', 'idx_group');
            $table->index('impact_level', 'idx_impact_level');
            $table->index('status', 'idx_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('violation_types');
    }
};
