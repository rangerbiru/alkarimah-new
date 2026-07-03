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
        Schema::create('employee_work_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');

            // Semua field nullable
            $table->string('company_name', 200)->nullable();
            $table->string('position', 100)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('reason_for_leaving')->nullable();
            $table->double('last_salary')->nullable();
            $table->string('supervisor_name', 100)->nullable();
            $table->string('supervisor_contact', 50)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_work_experiences');
    }
};
