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
        if (!Schema::hasTable('committee_documents')) {
            Schema::create('committee_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('committee_activity_id')->constrained()->onDelete('cascade');
                $table->string('file_path');        // path file di storage (misal: 'documents/sk-2025.pdf')
                $table->string('file_type');        // contoh: 'photo', 'sk', 'berita_acara', 'other'
                $table->string('file_name');        // nama asli file (opsional, untuk tampilan)
                $table->string('description')->nullable(); // keterangan tambahan (opsional)
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('committee_documents');
    }
};
