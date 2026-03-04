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
            $table->longText('bills')->nullable()->comment('flag-1: id dari transaction_bill flag-2: saldo sebelum dan sesudah flag-3: id dari savings_withdrawal')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            $table->longText('bills')->nullable()->comment('flag 1: id dari transaction_bill flag 2 & 3 : saldo sebelum dan sesudah')->change();
        });
    }
};
