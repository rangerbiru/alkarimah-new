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
        Schema::table('transaction', function (Blueprint $table) {
            $table->tinyInteger('flag')->unsigned()->comment('1: Tagihan 2: Setor Tabungan 3: Pengambilan Tabungan 4: Topup Saldo')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            $table->tinyInteger('flag')->unsigned()->comment('1: Tagihan 2: Setor Tabungan 3: Pengambilan Tabungan')->change();
        });
    }
};
