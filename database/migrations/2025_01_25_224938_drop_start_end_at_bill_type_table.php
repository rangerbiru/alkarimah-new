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
            $table->dropColumn('start_month');
            $table->dropColumn('start_year');
            $table->dropColumn('end_month');
            $table->dropColumn('end_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_type', function (Blueprint $table) {
            $table->string('start_month', 2)->nullable()->after('spp');
            $table->year('start_year')->nullable()->after('start_month');
            $table->string('end_month', 2)->nullable()->after('start_year');
            $table->year('end_year')->nullable()->after('end_month');
        });
    }
};
