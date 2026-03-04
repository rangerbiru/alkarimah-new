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
        if (!Schema::hasTable('permit_types')) {
            Schema::create('permit_types', function (Blueprint $table) {
                $table->id();
                $table->string('permit_type');
                $table->integer('level');
                $table->text('description')->nullable();
                $table->enum('wage_status', ['y', 'n'])->default('y');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permit_types');
    }
};
