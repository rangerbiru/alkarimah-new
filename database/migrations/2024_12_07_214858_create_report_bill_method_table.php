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
        Schema::create('report_bill_method', function (Blueprint $table) {
            $table->id();
            $table->date('dates');
            $table->tinyInteger('method')->comment('1: Cash 2: BNI 3: BSI 4: Saldo Topup');
            $table->integer('quantity');
            $table->double('total');
            $table->bigInteger('branch_id')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_bill_method');
    }
};
