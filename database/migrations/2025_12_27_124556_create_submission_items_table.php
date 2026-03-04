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
        Schema::create('submission_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submissions_id')->constrained()->onDelete('cascade');
            $table->foreignId('items_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->enum('location', ['ma', 'mts', 'pkpps', 'umum', 'asrama'])->default('umum');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_items');
    }
};
