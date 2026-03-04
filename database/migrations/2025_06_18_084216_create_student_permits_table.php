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
        if (!Schema::hasTable('student_permits')) {
            Schema::create('student_permits', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('student_id')->unsigned();
                $table->bigInteger('student_permit_group_id')->unsigned();
                $table->timestamp('permit_start_date');
                $table->timestamp('permit_end_date')->nullable();
                $table->string('purpose');
                $table->string('destination')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
                $table->text('notes')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->comment('Ustadz yang menyetujui izin');
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_permits');
    }
};
