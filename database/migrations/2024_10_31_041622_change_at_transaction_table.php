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
            $table->longText('bills')->nullable()->comment('flag 1: id dari transaction_bill flag 2 & 3 : saldo sebelum dan sesudah')->change();
            $table->double('donation')->default(0)->change();
            $table->double('subtotal')->after('bills');
            $table->tinyInteger('flag')->comment('1: Tagihan 2: Setor Tabungan 3: Pengambilan Tabungan')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            $table->longText('bills')->comment('id dari tabel transaction_bill')->change();
            $table->double('donation')->change();
            $table->dropColumn('subtotal');
            $table->dropColumn('flag');
        });
    }
};
