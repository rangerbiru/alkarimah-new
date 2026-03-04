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
        if (!Schema::hasTable('actual_submission_items')) {
            Schema::create('actual_submission_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('submissions_id')->constrained()->onDelete('cascade');
                $table->foreignId('items_id')->constrained()->onDelete('cascade');
                $table->integer('quantity');
                $table->integer('price');
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actual_submission_items');
    }
};
