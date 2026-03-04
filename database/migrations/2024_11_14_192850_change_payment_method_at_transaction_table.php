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
            $table->tinyInteger('payment_method')->nullable()->comment('1: Tunai 2: BNI 3: BSI 4: Saldo Topup')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            $table->tinyInteger('payment_method')->nullable()->comment('1: Tunai 2: BNI 3: BSI')->change();
        });
    }
};
