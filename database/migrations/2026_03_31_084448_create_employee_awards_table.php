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
        Schema::create('employee_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');

            $table->string('award_name', 200)->nullable();
            $table->string('awarded_by', 200)->nullable();
            $table->date('received_date')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_awards');
    }
};
