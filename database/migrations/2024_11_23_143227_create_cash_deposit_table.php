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
        Schema::create('cash_deposit', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20);
            $table->date('dates');
            $table->longText('transactions');
            $table->double('total');
            $table->tinyInteger('status')->default(0)->comment('0: Menunggu Persetujuan 1: Diterima 2: Ditolak');
            $table->dateTime('verified_at')->nullable();
            $table->bigInteger('verified_by')->unsigned()->nullable();
            $table->text('reason')->nullable()->comment('Alasan ditolak');
            $table->bigInteger('branch_id')->unsigned();
            $table->timestamp('created_at')->useCurrent();
            $table->bigInteger('created_by')->unsigned();
            $table->timestamp('updated_at')->nullable();
            $table->bigInteger('updated_by')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_deposit');
    }
};
