<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak penggabungan akun pegawai-orang tua beserta data rollback. Sengaja tabel sendiri
     * (bukan activity_log) agar tidak terkena pembersihan otomatis.
     */
    public function up(): void
    {
        Schema::create('account_link_log', function (Blueprint $table) {
            $table->id();
            $table->string('action', 10); // link | unlink
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('employee_user_id')->nullable();
            $table->unsignedBigInteger('old_user_id')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->json('before'); // nilai sebelum: parent.id_user, parent.phone, user lama
            $table->json('moved');  // "tabel.kolom" => [primary key baris yang dipindah]
            $table->unsignedBigInteger('link_id')->nullable(); // untuk action unlink: id entri link yang dibatalkan
            $table->timestamp('rolled_back_at')->nullable();
            $table->unsignedBigInteger('rolled_back_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['parent_id', 'action', 'rolled_back_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_link_log');
    }
};
