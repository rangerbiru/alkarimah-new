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
        Schema::create('activity_plans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('year_id')->constrained('year')->onDelete('cascade');

            $table->unsignedBigInteger('employee_id')->comment('PIC / Penanggung Jawab');
            $table->string('unit')->nullable()->comment('Bagian / Unit');

            $table->string('name')->comment('Nama Kegiatan');
            $table->string('activity_type')->comment('Jenis: Rutin, Administratif, Program');
            $table->enum('frequency', ['harian', 'mingguan', 'bulanan', 'semesteran', 'tahunan', 'kondisional']);
            $table->string('schedule_details')->nullable()->comment('Contoh: Setiap Senin, Tanggal 25, dsb');
            $table->string('target_duration')->nullable()->comment('Contoh: 45 menit, 2 hari');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->text('expected_output')->nullable();
            $table->boolean('is_proof_required')->default(false)->comment('Wajib upload bukti: ya/tidak');

            $table->enum('task_source', ['terencana', 'pegawai', 'pimpinan'])->default('terencana');

            $table->enum('status', ['draft', 'diajukan', 'perlu_revisi', 'disetujui', 'aktif', 'arsip'])->default('draft');
            $table->text('reject_reason')->nullable()->comment('Catatan revisi dari pimpinan');

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
        Schema::dropIfExists('activity_plans');
    }
};
