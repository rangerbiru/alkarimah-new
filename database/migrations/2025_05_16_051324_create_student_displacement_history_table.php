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
        Schema::create('student_displacement_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_student')->unsigned();
            $table->bigInteger('before_class_id')->unsigned();
            $table->bigInteger('before_nis')->unsigned();
            $table->bigInteger('after_class_id')->unsigned();
            $table->bigInteger('after_nis')->unsigned();
            $table->unsignedBigInteger('branch_id');
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_displacement_history');
    }
};
