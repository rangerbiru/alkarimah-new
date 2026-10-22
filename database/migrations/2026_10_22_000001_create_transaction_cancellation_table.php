<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_cancellation', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_transaction')->unique(); // pengaman terakhir: tidak bisa dibatalkan dua kali
            $table->text('reason');
            $table->unsignedBigInteger('cancelled_by');
            $table->dateTime('cancelled_at');
            $table->longText('snapshot')->nullable(); // JSON audit, tidak dipakai menjalankan logika
            $table->unsignedBigInteger('branch_id');
            $table->timestamps();
            $table->index('cancelled_at');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_cancellation');
    }
};
