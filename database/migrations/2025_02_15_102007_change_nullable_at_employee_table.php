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
        Schema::table('employee', function (Blueprint $table) {
            $table->string('nip', 50)->nullable()->change();
            $table->string('nik', 20)->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->text('placement')->nullable()->comment('Lokasi pegawai')->change();
            $table->text('task_main')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->string('nip', 50)->change();
            $table->string('nik', 20)->change();
            $table->text('address')->change();
            $table->text('placement')->comment('Lokasi pegawai')->change();
            $table->text('task_main')->change();
        });
    }
};
