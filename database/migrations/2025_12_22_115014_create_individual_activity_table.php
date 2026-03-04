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
        if (!Schema::hasTable('individual_activity')) {
            Schema::create('individual_activity', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('id_employee');
                $table->unsignedBigInteger('id_activity');
                $table->text('description')->nullable();
                $table->text('comment')->nullable();
                $table->unsignedBigInteger('comment_by')->nullable();
                $table->text('photo')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('individual_activity');
    }
};
