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
        Schema::create('employee_education', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');

            $table->enum('level', ['elementary', 'junior_high', 'senior_high', 'diploma', 'bachelor', 'master', 'doctorate'])->nullable();
            $table->string('institution_name', 200)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('major', 100)->nullable();
            $table->decimal('gpa', 3, 2)->nullable();
            $table->year('start_year')->nullable();
            $table->year('end_year')->nullable();
            $table->date('graduation_date')->nullable();
            $table->string('certificate_path', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_education');
    }
};
