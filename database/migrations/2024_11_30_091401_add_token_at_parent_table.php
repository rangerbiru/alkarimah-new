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
        Schema::table('parent', function (Blueprint $table) {
            $table->string('token', 6)->nullable()->comment('Token kode verifikasi pendaftaran')->after('balance');
            $table->dateTime('token_expired_at')->nullable()->after('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent', function (Blueprint $table) {
            $table->dropColumn('token');
            $table->dropColumn('token_expired_at');
        });
    }
};
