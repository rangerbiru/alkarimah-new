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
        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_parent')->unsigned()->nullable();
            $table->string('name', 50);
            $table->string('icon', 100)->nullable();
            $table->text('route')->nullable();
            $table->enum('group', ['none', 'akademik', 'keuangan', 'pengaturan'])->default('none');
            $table->text('actions')->nullable();
            $table->tinyInteger('sort');
            $table->boolean('is_parent')->default(false);
            $table->boolean('is_sidebar')->default(true)->comment('1: Sidebar Menu 0: Header Menu');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu');
    }
};
