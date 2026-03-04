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
        Schema::table('transaction_bill', function (Blueprint $table) {
            $table->bigInteger('id_transaction')->unsigned()->nullable()->comment('Jika sedang menunggu pembayaran / sudah lunas, maka diisi id dari tabel transaction')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_bill', function (Blueprint $table) {
            $table->bigInteger('id_transaction')->unsigned()->nullable()->comment('Jika Lunas, Diisi id dari tabel transaction	')->change();
        });
    }
};
