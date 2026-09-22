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
        // Jenjang (TK/SD/SMP/SMA) yang dipegang oleh user role kepala sekolah
        Schema::create('user_education_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('level_education', ['tk', 'sd', 'smp', 'sma']);
            $table->timestamps();

            $table->unique(['user_id', 'level_education']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_education_levels');
    }
};
