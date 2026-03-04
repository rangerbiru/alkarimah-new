<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('teaching_journals')) {
            Schema::create('teaching_journals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_class_hour_details');
                $table->date('date'); // Tanggal pertemuan
                $table->string('bab')->nullable();
                $table->text('keterangan')->nullable();
                $table->timestamps();

                // Foreign key
                $table->foreign('id_class_hour_details')
                    ->references('id')
                    ->on('class_hour_details')
                    ->onDelete('cascade');

                // Unique agar tidak duplikat jurnal per sesi & tanggal
                $table->unique(['id_class_hour_details', 'date']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('teaching_journals');
    }
};
