<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * module_id NULL  = jenjang milik akun itu sendiri (akun kepala-sekolah / kasir-unit lama).
     * module_id terisi = jenjang untuk modul tertentu yang dipegang akun pegawai.
     */
    public function up(): void
    {
        Schema::table('user_education_levels', function (Blueprint $table) {
            $table->unsignedBigInteger('module_id')->nullable()->after('user_id');
            $table->unique(['user_id', 'module_id', 'level_education'], 'user_education_levels_user_module_level_unique');
        });

        Schema::table('user_education_levels', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'level_education']);
        });
    }

    public function down(): void
    {
        Schema::table('user_education_levels', function (Blueprint $table) {
            $table->dropUnique('user_education_levels_user_module_level_unique');
            $table->dropColumn('module_id');
        });

        Schema::table('user_education_levels', function (Blueprint $table) {
            $table->unique(['user_id', 'level_education']);
        });
    }
};
