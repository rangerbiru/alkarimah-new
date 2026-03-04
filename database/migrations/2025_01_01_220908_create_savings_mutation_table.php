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
        Schema::create('savings_mutation', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_student')->unsigned();
            $table->bigInteger('id_transaction')->unsigned()->comment('flag-1: id dari transaction flag-2: id dari savings_withdrawal');
            $table->double('debit');
            $table->double('credit');
            $table->double('balance');
            $table->tinyInteger('flag')->unsigned()->comment('1: Setor Tabungan 2: Pengambilan Tabungan');
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
        Schema::dropIfExists('savings_mutation');
    }
};
