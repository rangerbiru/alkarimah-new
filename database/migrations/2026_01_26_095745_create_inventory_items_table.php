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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('asset_id')->unique();
            $table->string('inventory_code')->unique();
            $table->string('name');
            $table->string('category');
            $table->string('brand')->nullable();
            $table->text('specification')->nullable();
            $table->string('location')->nullable();
            $table->string('unit')->nullable();
            $table->string('responsible_person')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->string('source_funding')->nullable();

            $table->decimal('acquisition_price', 12, 2)->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('total_acquisition_value', 12, 2)->nullable();
            $table->decimal('residual_value', 12, 2)->nullable();
            $table->integer('useful_life_years')->nullable();

            $table->string('depreciation_method')->nullable();
            $table->decimal('depreciation_amount_per_year', 12, 2)->nullable();
            $table->decimal('depreciation_amount_per_month', 12, 2)->nullable();

            $table->date('used_until_date')->nullable();
            $table->decimal('accumulated_depreciation', 12, 2)->nullable();
            $table->decimal('book_value', 12, 2)->nullable();

            $table->string('condition')->nullable();
            $table->string('status')->default('Aktif');
            $table->string('serial_number')->nullable();
            $table->text('documents')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
