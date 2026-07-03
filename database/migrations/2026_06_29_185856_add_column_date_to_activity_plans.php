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
        Schema::table('activity_plans', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('frequency')->comment('Tanggal mulai');
            $table->date('end_date')->nullable()->after('start_date')->comment('Tanggal selesai (opsional)');
            $table->time('start_time')->nullable()->after('end_date')->comment('Jam mulai');
            $table->time('end_time')->nullable()->after('start_time')->comment('Jam selesai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_plans', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'start_time', 'end_time']);
        });
    }
};
