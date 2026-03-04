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
        Schema::create('employee', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_user')->unsigned();
            $table->string('nip', 50);
            $table->string('nik', 20)->nullable();
            $table->string('name', 150);
            $table->enum('gender', ['male', 'female']);
            $table->string('phone', 20);
            $table->string('email', 200)->nullable();
            $table->text('address');
            $table->string('education', 100)->nullable();
            $table->boolean('marital_status')->comment('0: Belum Menikah 1: Menikah');
            $table->text('placement')->comment('Lokasi pegawai');
            $table->text('task_main');
            $table->text('task_additional')->nullable();
            $table->boolean('status')->default(1)->comment('0: Tidak Aktif 1: Aktif');
            $table->boolean('status_employment')->comment('0: Tidak Tetap 1: Tetap');
            $table->boolean('status_teacher')->comment('0: Bukan 1: Ya, Pengajar');
            $table->bigInteger('branch_id')->unsigned();
            $table->timestamp('created_at')->useCurrent();
            $table->bigInteger('created_by')->unsigned();
            $table->timestamp('updated_at')->nullable();
            $table->bigInteger('updated_by')->unsigned()->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->bigInteger('deleted_by')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee');
    }
};
