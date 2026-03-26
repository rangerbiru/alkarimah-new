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
        if (!Schema::hasTable('student_violations')) {
            Schema::create('student_violations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id');
                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
                $table->unsignedBigInteger('violation_id');
                $table->foreign('violation_id')->references('id')->on('violation_types')->onDelete('cascade');
                $table->unsignedBigInteger('employee_id');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->date('date');
                $table->time('time');
                $table->string('location');
                $table->string('notes')->nullable();
                $table->string('proof')->nullable();
                $table->enum('status', ['draft', 'tabayyun', 'disahkan'])->default('draft');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_violations');
    }
};
