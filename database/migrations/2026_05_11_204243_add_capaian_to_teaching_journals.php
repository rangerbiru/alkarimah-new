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
        if (Schema::hasTable('teaching_journals')) {
            Schema::table('teaching_journals', function (Blueprint $table) {
                if (! Schema::hasColumn('teaching_journals', 'class_condition')) {
                    $table->string('class_condition', 50)->nullable()->after('subject_matter');
                }
                if (! Schema::hasColumn('teaching_journals', 'trouble')) {
                    $table->text('trouble')->nullable()->after('class_condition');
                }
                if (! Schema::hasColumn('teaching_journals', 'follow_up')) {
                    $table->text('follow_up')->nullable()->after('trouble');
                }
                if (! Schema::hasColumn('teaching_journals', 'achievements')) {
                    $table->text('achievements')->nullable()->after('follow_up');
                }
                if (! Schema::hasColumn('teaching_journals', 'notes')) {
                    $table->text('notes')->nullable()->after('achievements');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teaching_journals', function (Blueprint $table) {
            //
        });
    }
};
