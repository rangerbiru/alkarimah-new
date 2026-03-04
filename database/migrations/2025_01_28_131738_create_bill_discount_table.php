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
        Schema::create('bill_discount', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_student')->unsigned();
            $table->bigInteger('id_bill')->unsigned();
            $table->bigInteger('id_year')->unsigned();
            $table->double('nominal');
            $table->text('applies_to')->nullable()->comment('monthly: yyyy-mm, semester: 1/2');
            $table->boolean('status')->default(1)->comment('0: Tidak Aktif 1: Aktif');
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
        Schema::dropIfExists('bill_discount');
    }
};
