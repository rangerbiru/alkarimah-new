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
        Schema::create('activity_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_plan_id')->constrained('activity_plans')->onDelete('cascade');
            $table->date('date')->comment('Tanggal pelaksanaan');

            $table->enum('status', [
                'belum', 'sedang', 'menunggu_validasi', 'selesai', 'tertunda', 'tidak_terlaksana',
            ])->default('belum');

            $table->string('proof_file')->nullable()->comment('Path file bukti/lampiran');
            $table->text('notes')->nullable()->comment('Catatan kendala/realisasi pegawai');

            $table->unsignedBigInteger('validated_by')->nullable()->comment('ID Pimpinan yang memvalidasi');
            $table->timestamp('validated_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_realizations');
    }
};
