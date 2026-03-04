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
        Schema::create('transaction', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_student')->unsigned();
            $table->bigInteger('id_donation')->unsigned()->nullable();
            $table->string('number', 20);
            $table->date('dates');
            $table->longText('bills')->comment('id dari tabel transaction_bill');
            $table->double('donation')->default(0);
            $table->double('total');
            $table->tinyInteger('payment_method')->nullable()->comment('1: Tunai 2: BNI 3: BSI');
            $table->dateTime('paid_at')->nullable();
            $table->bigInteger('paid_by')->nullable()->unsigned();
            $table->tinyInteger('status')->default(0)->comment('0: Belum Dibayar 1: Lunas');
            $table->bigInteger('branch_id')->unsigned();
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
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
