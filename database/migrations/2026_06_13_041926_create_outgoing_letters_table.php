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
        Schema::create('outgoing_letters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('letter_category_id')
                ->constrained('letter_categories')
                ->onDelete('cascade');

            $table->string('level')->nullable()->comment('Jenjang: madrasah_aliyah, mts, dll');
            $table->integer('sequence_number')->comment('Nomor Urut (Contoh: 1)');
            $table->string('letter_number')->unique()->comment('Nomor Surat Full (Contoh: 001/UND-MA/VI/2026)');
            $table->date('letter_date')->comment('Tanggal Surat');
            $table->string('destination')->comment('Tujuan Surat');
            $table->string('subject')->comment('Perihal Surat');
            $table->string('attachment')->nullable()->comment('Lampiran Surat');
            $table->string('priority')->comment('Sifat Surat (Biasa, Penting, Segera, dll)');
            $table->string('signer')->comment('Penandatangan');
            $table->string('status')->default('draft')->comment('Status Surat (Draft, Diajukan, Disetujui, Terkirim)');
            $table->text('summary')->nullable()->comment('Isi Ringkas / Keterangan');
            $table->string('file')->nullable()->comment('Path file upload surat');

            // Kolom standar sistem
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outgoing_letters');
    }
};
