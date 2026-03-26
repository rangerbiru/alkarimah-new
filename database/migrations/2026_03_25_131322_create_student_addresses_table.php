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
        Schema::create('student_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');

            // Home Address
            $table->text('home_address')->nullable();
            $table->string('home_district', 50)->nullable(); // Kecamatan
            $table->string('home_regency', 50)->nullable(); // Kabupaten
            $table->string('home_province', 50)->nullable();
            $table->string('postal_code', 10)->nullable();

            // Previous School Address
            $table->text('previous_school_address')->nullable();
            $table->string('previous_school_district', 50)->nullable();
            $table->string('previous_school_regency', 50)->nullable();
            $table->string('previous_school_province', 50)->nullable();

            // Distance
            $table->string('distance_to_school', 50)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_addresses');
    }
};
