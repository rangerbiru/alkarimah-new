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
        Schema::create('report_bill', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_type')->unsigned();
            $table->tinyInteger('level')->comment('SD: 1-6, SMP: 7-9, SMA: 10-12');
            $table->double('total')->default(0);
            $table->double('paid')->default(0);
            $table->double('remaining')->default(0);
            $table->bigInteger('branch_id')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_bill');
    }
};
