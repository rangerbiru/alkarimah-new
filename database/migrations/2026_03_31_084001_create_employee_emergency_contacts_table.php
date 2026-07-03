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
        if (!Schema::hasTable('employee_emergency_contacts')) {
            Schema::create('employee_emergency_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');

                $table->string('contact_name', 150)->nullable();
                $table->string('relationship', 50)->nullable();
                $table->text('address')->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('email', 100)->nullable();

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_emergency_contacts');
    }
};
