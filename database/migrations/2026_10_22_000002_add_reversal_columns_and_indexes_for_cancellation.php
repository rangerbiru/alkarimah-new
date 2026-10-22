<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * reversal_of = id baris asli yang dibalik oleh entri pembalik (NULL untuk baris biasa).
     * savings_mutation.id_transaction polimorfik: flag 1 (setoran) dan 3 (pembatalan) berisi
     * transaction.id, flag 2 (pengambilan) berisi savings_withdrawal.id, jadi lookup harus
     * ikut memfilter flag.
     */
    public function up(): void
    {
        Schema::table('topup_history', function (Blueprint $table) {
            $table->unsignedBigInteger('reversal_of')->nullable()->after('id_transaction');
            $table->index('reversal_of', 'topup_history_reversal_of_index');
            $table->index('id_transaction', 'topup_history_id_transaction_index');
        });

        Schema::table('savings_mutation', function (Blueprint $table) {
            $table->unsignedBigInteger('reversal_of')->nullable()->after('id_transaction');
            $table->index('reversal_of', 'savings_mutation_reversal_of_index');
            $table->index(['id_transaction', 'flag'], 'savings_mutation_id_transaction_flag_index');
        });

        Schema::table('transaction_bill', function (Blueprint $table) {
            $table->index('id_transaction', 'transaction_bill_id_transaction_index');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_bill', function (Blueprint $table) {
            $table->dropIndex('transaction_bill_id_transaction_index');
        });

        Schema::table('savings_mutation', function (Blueprint $table) {
            $table->dropIndex('savings_mutation_id_transaction_flag_index');
            $table->dropIndex('savings_mutation_reversal_of_index');
            $table->dropColumn('reversal_of');
        });

        Schema::table('topup_history', function (Blueprint $table) {
            $table->dropIndex('topup_history_id_transaction_index');
            $table->dropIndex('topup_history_reversal_of_index');
            $table->dropColumn('reversal_of');
        });
    }
};
