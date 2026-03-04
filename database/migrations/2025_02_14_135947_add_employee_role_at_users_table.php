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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super-admin', 'admin', 'kasir', 'bendahara', 'penanggung-jawab-tabungan', 'orang-tua', 'wali-kelas', 'pegawai'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super-admin', 'admin', 'kasir', 'bendahara', 'penanggung-jawab-tabungan', 'orang-tua', 'wali-kelas'])->change();
        });
    }
};
