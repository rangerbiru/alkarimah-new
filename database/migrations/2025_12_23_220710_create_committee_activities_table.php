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
        if (!Schema::hasTable('committee_activities')) {
            Schema::create('committee_activities', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('id_responsible_person');
                $table->date('activity_date');
                $table->string('related_field');        // Bidang Terkait
                $table->string('activity_type');        // Jenis Kegiatan
                $table->string('activity_name');        // Nama Kegiatan
                $table->string('responsible_person');   // Penanggung Jawab
                $table->string('location');             // Lokasi Kegiatan
                $table->integer('participant_count');   // Jumlah Peserta
                $table->text('activity_summary')->nullable(); // Ringkasan Kegiatan
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('committee_activities');
    }
};
