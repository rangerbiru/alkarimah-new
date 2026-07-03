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
        if (! Schema::hasTable('student_academics')) {
            Schema::create('student_academics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('student')->onDelete('cascade');

                $table->string('entry_year', 10)->nullable();
                $table->string('entry_class', 50)->nullable();
                $table->integer('attendance_number')->nullable();
                $table->integer('class_rank')->nullable();
                $table->string('major', 50)->nullable();
                $table->string('parallel_class', 50)->nullable();

                $table->string('parent_income_bracket', 50)->nullable();

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_academics');
    }
};
