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
        Schema::table('student', function (Blueprint $table) {
            $table->bigInteger('id_asrama')->unsigned()->nullable()->after('id_class');
            $table->bigInteger('id_halaqah')->unsigned()->nullable()->after('id_asrama');
            $table->string('card_number', 50)->nullable()->after('child');
            $table->string('nis_local', 50)->nullable()->after('nis');
            $table->text('address')->nullable()->after('religion');
            $table->date('entry_date')->nullable()->after('card_number');
            $table->double('spp')->nullable()->after('entry_date');
            $table->string('location', 200)->nullable()->after('spp');
            $table->bigInteger('file_photo')->unsigned()->nullable()->after('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student', function (Blueprint $table) {
            $table->dropColumn('id_asrama');
            $table->dropColumn('id_halaqah');
            $table->dropColumn('card_number');
            $table->dropColumn('nis_local');
            $table->dropColumn('address');
            $table->dropColumn('entry_date');
            $table->dropColumn('spp');
            $table->dropColumn('location');
            $table->dropColumn('file_photo');
        });
    }
};
