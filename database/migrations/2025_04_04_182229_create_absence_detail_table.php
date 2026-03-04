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
        Schema::create('absence_detail', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_absence')->unsigned();
            $table->bigInteger('id_student')->unsigned();
            $table->tinyInteger('status')->comment('0: Tidak Hadir 1: Hadir 2: Izin 3: Sakit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absence_detail');
    }
};
