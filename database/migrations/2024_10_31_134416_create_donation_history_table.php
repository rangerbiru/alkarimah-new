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
        Schema::create('donation_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_donation')->unsigned();
            $table->bigInteger('id_transaction')->unsigned();
            $table->bigInteger('id_student')->unsigned();
            $table->longText('description');
            $table->double('nominal');
            $table->dateTime('paid_at');
            $table->bigInteger('branch_id')->unsigned();
            $table->timestamp('created_at')->useCurrent();
            $table->bigInteger('created_by')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_history');
    }
};
