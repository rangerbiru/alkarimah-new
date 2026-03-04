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
            $table->double('subtotal')->default(0)->after('years');
            $table->double('discount')->default(0)->after('subtotal');
            $table->bigInteger('discount_id')->unsigned()->nullable()->after('discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_bill', function (Blueprint $table) {
            $table->dropColumn('subtotal');
            $table->dropColumn('discount');
            $table->dropColumn('discount_id');
        });
    }
};
