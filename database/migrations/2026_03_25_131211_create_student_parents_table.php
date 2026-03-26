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
        Schema::create('student_parents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');

            // Father
            $table->string('father_name', 50)->nullable();
            $table->char('father_id_number', 16)->nullable(); // NIK
            $table->string('father_status', 20)->nullable(); // e.g., Alive, Deceased
            $table->string('father_education', 20)->nullable();
            $table->string('father_occupation', 50)->nullable();
            $table->string('father_phone', 20)->nullable();

            // Mother
            $table->string('mother_name', 50)->nullable();
            $table->char('mother_id_number', 16)->nullable(); // NIK
            $table->string('mother_status', 20)->nullable();
            $table->string('mother_education', 20)->nullable();
            $table->string('mother_occupation', 50)->nullable();
            $table->string('mother_phone', 20)->nullable();

            // Guardian
            $table->string('guardian_name', 50)->nullable();
            $table->char('guardian_id_number', 16)->nullable();
            $table->string('guardian_occupation', 50)->nullable();
            $table->string('guardian_email', 100)->nullable();
            $table->string('guardian_phone', 20)->nullable();
            $table->string('guardian_income', 30)->nullable();

            // Family Info
            $table->char('family_card_number', 16)->nullable(); // No KK
            $table->string('family_income', 30)->nullable();
            $table->tinyInteger('child_order')->nullable(); // Anak ke-
            $table->tinyInteger('siblings_count')->nullable();
            $table->tinyInteger('step_siblings_count')->default(0);
            $table->tinyInteger('adopted_siblings_count')->default(0);
            $table->tinyInteger('family_members_count')->nullable(); // Jumlah Jiwa
            $table->string('orphan_status', 20)->nullable();

            // Notes
            $table->text('guardian_notes')->nullable();
            $table->string('approval_status', 10)->default('off');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_parents');
    }
};
