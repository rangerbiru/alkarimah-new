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
        Schema::create('student_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');

            $table->string('kk_number', 50)->nullable();
            $table->string('hobby', 100)->nullable();
            $table->string('ambition', 100)->nullable();
            $table->integer('sibling_count')->nullable();
            $table->string('financing_by', 50)->nullable();
            $table->string('phone', 25)->nullable();

            $table->string('province', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('postal_code', 10)->nullable();

            $table->string('distance_to_school', 50)->nullable();
            $table->string('transportation', 50)->nullable();

            $table->boolean('is_kk_submitted')->default(false);
            $table->boolean('is_akta_submitted')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_details');
    }
};
