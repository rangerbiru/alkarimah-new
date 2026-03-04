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
            $table->double('salary')->default(0)->after('task_additional');
            $table->double('salary_allowance')->default(0)->after('salary');
            $table->longText('salary_allowance_detail')->nullable()->after('salary_allowance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            $table->dropColumn('salary');
            $table->dropColumn('salary_allowance');
            $table->dropColumn('salary_allowance_detail');
        });
    }
};
