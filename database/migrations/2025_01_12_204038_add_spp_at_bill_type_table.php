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
        Schema::table('bill_type', function (Blueprint $table) {
            $table->boolean('spp')->comment('0: Tidak 1: Ya')->after('period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_type', function (Blueprint $table) {
            $table->dropColumn('spp');
        });
    }
};
