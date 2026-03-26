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
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');

            // Physical Data
            $table->decimal('weight', 5, 2)->nullable()->comment('in KG');
            $table->decimal('height', 5, 2)->nullable()->comment('in CM');
            $table->string('blood_type', 5)->nullable();

            // Health & Habits
            $table->text('medical_history')->nullable();
            $table->text('physical_disabilities')->nullable();
            $table->text('daily_habits')->nullable();
            $table->string('personality', 100)->nullable();

            // Personal Info
            $table->string('nickname', 100)->nullable();
            $table->string('religion', 50)->nullable();
            $table->string('home_language', 100)->nullable();
            $table->string('living_with_parents', 100)->nullable();
            $table->string('photo_path', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
