<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Di database produksi student.id_parent sudah nullable (ada santri tanpa data orang tua); migration
     * awal membuatnya NOT NULL. Menyamakan skema, tidak mengubah data.
     */
    public function up(): void
    {
        Schema::table('student', function (Blueprint $table) {
            $table->unsignedBigInteger('id_parent')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Sengaja kosong: produksi memang nullable; mengembalikan NOT NULL akan gagal bila ada baris NULL.
    }
};
