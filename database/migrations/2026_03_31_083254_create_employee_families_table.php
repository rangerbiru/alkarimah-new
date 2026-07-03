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
        if (!Schema::hasTable('employee_families')) {
            Schema::create('employee_families', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');

                $table->enum('relationship', ['suami', 'istri', 'ayah', 'ibu', 'anak'])->nullable();
                $table->string('full_name', 150);
                $table->enum('gender', ['male', 'female']);
                $table->string('birth_place', 100)->nullable();
                $table->date('birth_date')->nullable();
                $table->date('death_date')->nullable();
                $table->string('education_last', 50)->nullable();
                $table->string('occupation', 100)->nullable();

                $table->text('address')->nullable();
                $table->string('rt_rw', 20)->nullable();
                $table->string('village', 100)->nullable();
                $table->string('district', 100)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('province', 100)->nullable();
                $table->string('postal_code', 10)->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('mobile_phone', 20)->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->foreign('employee_id')->references('id')->on('employee')->onDelete('cascade');
                $table->index(['employee_id', 'relationship', 'full_name']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_families');
    }
};
