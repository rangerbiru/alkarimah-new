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
        if (!Schema::hasTable('employee_permits')) {
            Schema::create('employee_permits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->foreign('employee_id')->references('id')->on('employee')->onDelete('cascade');
                $table->unsignedBigInteger('permit_type_id');
                $table->foreign('permit_type_id')->references('id')->on('permit_types')->onDelete('cascade');
                $table->unsignedBigInteger('department_id');
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
                $table->string('name');
                $table->time('permit_start_time')->nullable();
                $table->integer('permit_hour_total')->nullable();
                $table->integer('permit_day_total')->nullable();
                $table->date('date');
                $table->string('reason');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->string('attachment')->nullable();
                $table->unsignedBigInteger('decision_by')->nullable();
                $table->string('note')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_permits');
    }
};
