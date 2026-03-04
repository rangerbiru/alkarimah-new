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
        Schema::create('moota_log', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_transaction')->unsigned();
            $table->bigInteger('id_branch')->unsigned();
            $table->longText('data');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moota_log');
    }
};
