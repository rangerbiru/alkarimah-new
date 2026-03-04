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
        Schema::table('report_bill_class', function (Blueprint $table) {
            $table->bigInteger('id_year')->unsigned()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_bill_class', function (Blueprint $table) {
            $table->dropColumn('id_year');
        });
    }
};
