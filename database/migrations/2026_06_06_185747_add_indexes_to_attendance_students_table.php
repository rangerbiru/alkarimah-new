<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('attendance_students', function (Blueprint $table) {
            $table->index('date');
            $table->index('id_student');

            $table->index('status');
        });
    }

    public function down()
    {
        Schema::table('attendance_students', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->dropIndex(['id_student']);
            $table->dropIndex(['status']);
        });
    }
};
