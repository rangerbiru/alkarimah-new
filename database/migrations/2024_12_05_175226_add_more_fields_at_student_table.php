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
            $table->enum('gender', ['male', 'female'])->after('name');
            $table->string('nisn', 20)->after('gender')->nullable();
            $table->string('nik', 16)->after('nisn')->nullable();
            $table->date('birthdate')->after('nik')->nullable();
            $table->string('birthplace', 100)->after('birthdate')->nullable();
            $table->enum('religion', ['Islam', 'Kristen', 'Hindu', 'Budha'])->after('birthplace');
            $table->string('school_from', 100)->after('religion')->nullable();
            $table->tinyInteger('child')->comment('Anak ke-n')->after('school_from')->nullable();
            $table->boolean('status')->default(1)->comment('0: Tidak Aktif 1: Aktif')->after('balance_savings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student', function (Blueprint $table) {
            $table->dropColumn('gender');
            $table->dropColumn('nisn');
            $table->dropColumn('nik');
            $table->dropColumn('birthdate');
            $table->dropColumn('birthplace');
            $table->dropColumn('religion');
            $table->dropColumn('school_from');
            $table->dropColumn('child');
            $table->dropColumn('status');
        });
    }
};
