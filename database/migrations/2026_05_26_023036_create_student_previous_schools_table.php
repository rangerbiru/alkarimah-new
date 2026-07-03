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
        Schema::create('student_previous_schools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');

            $table->string('school_name', 150)->nullable();
            $table->string('school_type', 50)->nullable();
            $table->string('school_status', 50)->nullable();
            $table->string('school_city', 100)->nullable();
            $table->string('npsn', 20)->nullable();

            $table->string('un_participant_number', 50)->nullable();
            $table->string('skhu_number', 50)->nullable();
            $table->string('ijazah_number', 50)->nullable();
            $table->date('skhu_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_previous_schools');
    }
};
