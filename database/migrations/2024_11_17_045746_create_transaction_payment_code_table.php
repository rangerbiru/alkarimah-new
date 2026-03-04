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
        Schema::create('transaction_payment_code', function (Blueprint $table) {
            $table->id();
            $table->double('code');
            $table->tinyInteger('status')->unsigned()->default(0)->comment('0: Belum Dipakai 1: Sudah Dipakai');
            $table->dateTime('expired_at');
            $table->tinyInteger('flag')->unsigned()->comment('1: Tagihan 2: Setor Tabungan 4: Topup Saldo (sama kaya flag di transaction)');
            $table->bigInteger('created_by')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_payment_code');
    }
};
