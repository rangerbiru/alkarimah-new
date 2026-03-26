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
        Schema::create('student_academics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');

            // Previous School
            $table->string('previous_school_name', 100)->nullable();
            $table->string('previous_school_npsn', 20)->nullable();
            $table->string('previous_school_status', 20)->nullable(); // Public/Private

            // Registration
            $table->string('registration_number', 100)->nullable();
            $table->string('session_id', 100)->nullable(); // Ensure this is not PHP Session
            $table->date('entry_date')->nullable();

            // Financial & Scholarship
            $table->string('payment_status', 20)->default('normal');
            $table->boolean('has_scholarship')->default(false);
            $table->string('scholarship_name', 100)->nullable();

            // Achievement & Status
            $table->string('achievements', 255)->nullable();
            $table->enum('recommendation_status', ['Recommended', 'Not Recommended'])->default('Recommended');
            $table->enum('graduation_status', ['passed', 'failed', 'reserve', ''])->nullable();
            $table->text('notes')->nullable();

            // Citizenship
            $table->string('nationality', 50)->default('Indonesia');
            $table->string('foreign_origin', 100)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_academics');
    }
};
