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
        Schema::table('transaction', function (Blueprint $table) {
            $table->tinyInteger('status')->default(0)->comment('0: Belum Dibayar 1: Sudah Dibayar 2: Expired')->change();
            $table->dateTime('expired_at')->nullable()->comment('+2 hari, ini tgl. expired sebenernya')->after('status');
            $table->dateTime('expired_view_at')->nullable()->comment('+1 hari, ini tgl. expired yg di show ke ortu')->after('expired_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            $table->tinyInteger('status')->default(0)->comment('0: Belum Dibayar 1: Sudah Dibayar')->change();
            $table->dropColumn('expired_at');
            $table->dropColumn('expired_view_at');
        });
    }
};
