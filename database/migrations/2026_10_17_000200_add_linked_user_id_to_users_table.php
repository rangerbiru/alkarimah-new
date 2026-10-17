<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Akun alias: akun orang tua lama yang digabung ke akun pegawai. Tetap bisa dipakai login,
     * tetapi sesi yang terbentuk selalu milik akun pegawai (users.linked_user_id).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('linked_user_id')->nullable()->after('role');
            $table->index('linked_user_id');
            $table->foreign('linked_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['linked_user_id']);
            $table->dropIndex(['linked_user_id']);
            $table->dropColumn('linked_user_id');
        });
    }
};
