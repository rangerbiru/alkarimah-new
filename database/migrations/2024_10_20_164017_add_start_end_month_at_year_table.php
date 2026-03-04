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
        Schema::table('year', function (Blueprint $table) {
            $table->string('start_month', 2)->after('start_year');
            $table->string('end_month', 2)->after('end_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('year', function (Blueprint $table) {
            $table->dropColumn('start_month');
            $table->dropColumn('end_month');
        });
    }
};
