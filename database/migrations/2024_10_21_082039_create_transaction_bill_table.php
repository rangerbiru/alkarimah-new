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
        Schema::create('transaction_bill', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_student')->unsigned();
            $table->bigInteger('id_bill')->unsigned();
            $table->bigInteger('id_transaction')->unsigned()->nullable()->comment('Jika Lunas, Diisi id dari tabel transaction');
            $table->tinyInteger('semester')->nullable();
            $table->tinyInteger('months');
            $table->year('years');
            $table->double('total');
            $table->tinyInteger('status')->default(0)->comment('0: Belum Dibayar 1: Lunas');
            $table->bigInteger('branch_id')->unsigned();
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction');
    }
};
