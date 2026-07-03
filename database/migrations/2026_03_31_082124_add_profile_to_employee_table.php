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
        if (Schema::hasTable('employee')) {
            Schema::table('employee', function (Blueprint $table) {
                $table->date('birth_date')->nullable();
                $table->string('birth_place', 100)->nullable();
                $table->string('religion', 50)->nullable();
                $table->string('ethnicity', 50)->nullable();
                $table->string('nationality', 50)->default('Indonesia')->nullable();
                $table->string('rt', 50)->nullable();
                $table->string('rw', 50)->nullable();
                $table->string('village', 100)->nullable();
                $table->string('district', 100)->nullable();
                $table->string('province', 100)->nullable();
                $table->string('postal_code', 50)->nullable();
                $table->string('home_phone', 50)->nullable();

                //identity
                $table->string('identity_number', 50)->nullable();
                $table->string('npwp_number', 50)->nullable();
                $table->string('bpjs_work_number', 50)->nullable();
                $table->string('bpjs_health_number', 50)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            //
        });
    }
};
