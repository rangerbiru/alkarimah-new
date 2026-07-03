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
        Schema::create('employee_health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');

            $table->string('disease_type', 100)->nullable();
            $table->date('illness_start_date')->nullable();
            $table->date('illness_end_date')->nullable();
            $table->text('medical_treatment')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_health_records');
    }
};
