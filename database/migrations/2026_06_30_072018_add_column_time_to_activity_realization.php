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
        Schema::table('activity_realizations', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('date')->comment('Jam mulai pelaksanaan');
            $table->time('end_time')->nullable()->after('start_time')->comment('Jam selesai pelaksanaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_realizations', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
