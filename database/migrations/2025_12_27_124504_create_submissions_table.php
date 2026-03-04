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
        if (!Schema::hasTable('submissions')) {
            Schema::create('submissions', function (Blueprint $table) {
                $table->id();
                $table->string('activity_name');
                $table->enum('activity_type', ['item', 'fund', 'service'])->default('item');
                $table->text('description')->nullable();

                $table->foreignId('employee_id')->constrained()->onDelete('cascade');
                $table->enum('status', ['pending', 'approved', 'rejected', 'process'])->default('pending');
                $table->enum('approve1', ['pending', 'approved', 'rejected'])->default('pending');
                $table->enum('approve2', ['pending', 'approved', 'rejected'])->default('pending');
                $table->enum('last_approve', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('reject_reason')->nullable();
                $table->unsignedBigInteger('rejected_by')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
