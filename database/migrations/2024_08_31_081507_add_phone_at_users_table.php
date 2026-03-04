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
            $table->enum('role', ['super-admin', 'admin', 'kasir', 'bendahara', 'penanggung-jawab-tabungan', 'orang-tua', 'wali-kelas'])->after('password');
            $table->string('phone', 20)->after('role');
            $table->timestamp('lastlogin_at')->after('remember_token')->nullable();
            $table->bigInteger('branch_id')->after('lastlogin_at')->nullable();
            $table->bigInteger('created_by')->after('created_at');
            $table->bigInteger('updated_by')->after('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->dropColumn('phone');
            $table->dropColumn('lastlogin_at');
            $table->dropColumn('branch_id');
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by');
        });
    }
};
