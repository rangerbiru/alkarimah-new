<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $base = ['super-admin', 'admin', 'kasir', 'bendahara', 'penanggung-jawab-tabungan', 'orang-tua', 'wali-kelas', 'pegawai', 'kepala-sekolah'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->setEnum([...$this->base, 'kasir-tabungan', 'kasir-unit']);

        DB::table('users')->where('role', 'kasir-tabungan')->update(['role' => 'kasir-unit']);

        $this->setEnum([...$this->base, 'kasir-unit']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->setEnum([...$this->base, 'kasir-tabungan', 'kasir-unit']);

        DB::table('users')->where('role', 'kasir-unit')->update(['role' => 'kasir-tabungan']);

        $this->setEnum([...$this->base, 'kasir-tabungan']);
    }

    private function setEnum(array $values): void
    {
        Schema::table('users', function (Blueprint $table) use ($values) {
            $table->enum('role', $values)->change();
        });
    }
};
