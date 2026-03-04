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
        Schema::create('topup_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_parent')->unsigned();
            $table->bigInteger('id_transaction')->unsigned();
            $table->double('debit')->default(0);
            $table->double('credit')->default(0);
            $table->double('balance');
            $table->timestamp('created_at')->useCurrent();
            $table->bigInteger('created_by')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topup_history');
    }
};
