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
            $table->bigInteger('id_village')->unsigned()->nullable()->after('id_user');
            $table->bigInteger('id_relation')->unsigned()->nullable()->after('id_village');
            $table->text('address')->nullable()->after('gender');
            $table->string('work', 200)->nullable()->after('address');
            $table->double('income')->nullable()->after('work');
            $table->string('phone', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent', function (Blueprint $table) {
            $table->dropColumn('id_village');
            $table->dropColumn('id_relation');
            $table->dropColumn('address');
            $table->dropColumn('work');
            $table->dropColumn('income');
            $table->string('phone', 20)->change();
        });
    }
};
