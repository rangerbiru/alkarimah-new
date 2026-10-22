<?php

namespace Tests\Feature;

use App\Constants\Modules;
use App\Enums\SavingsMutationFlag;
use App\Enums\TransactionFlag;
use App\Enums\TransactionMethod;
use App\Enums\TransactionStatus;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bagian A fitur Pembatalan Transaksi: skema, enum, konstanta, config, modul.
 */
class CancellationSchemaTest extends ModuleAccessTestCase
{
    public function test_enum_and_constant_values(): void
    {
        $this->assertSame('3', TransactionStatus::Cancelled->value);
        $this->assertSame('3', SavingsMutationFlag::Cancellation->value);
        $this->assertSame(11, Modules::CancelTransaction);
        $this->assertContains(Modules::CancelTransaction, Modules::Groups['tu']);

        // Nilai lama tidak bergeser
        $this->assertSame(['0', '1', '2', '3'], array_column(TransactionStatus::cases(), 'value'));
        $this->assertSame(['1', '2', '3'], array_column(SavingsMutationFlag::cases(), 'value'));
        $this->assertSame(5, Modules::KepalaSekolah);
    }

    public function test_config_follows_client_decisions(): void
    {
        $config = config('transaction-cancellation');

        $this->assertSame(['cash_deposit' => true, 'month_open' => false, 'class_unchanged' => true], $config['rules']);
        $this->assertSame(['min_words' => 3, 'min_chars' => 10], $config['reason']);
    }

    public function test_scope_covers_only_transfer_and_balance_payments_made_by_parents(): void
    {
        $scope = config('transaction-cancellation.scope');
        $bni = TransactionMethod::BNI->value;
        $bsi = TransactionMethod::BSI->value;
        $balance = TransactionMethod::TopupBalance->value;

        $this->assertEqualsCanonicalizing([
            TransactionFlag::Tagihan->value,
            TransactionFlag::SetorTabungan->value,
            TransactionFlag::TopupSaldo->value,
        ], array_keys($scope));
        $this->assertEqualsCanonicalizing([$bni, $bsi, $balance], $scope[TransactionFlag::Tagihan->value]);
        $this->assertEqualsCanonicalizing([$bni, $bsi], $scope[TransactionFlag::SetorTabungan->value]);
        $this->assertEqualsCanonicalizing([$bni, $bsi], $scope[TransactionFlag::TopupSaldo->value]);

        foreach ($scope as $methods) {
            $this->assertNotContains(TransactionMethod::Cash->value, $methods);
        }

        $this->assertArrayNotHasKey(TransactionFlag::PengambilanTabungan->value, $scope);
    }

    public function test_cancel_module_exists_and_nobody_holds_it(): void
    {
        $module = DB::table('module')->where('id', Modules::CancelTransaction)->first();

        $this->assertNotNull($module);
        $this->assertSame('Pembatalan Transaksi', $module->name);
        $this->assertSame(0, DB::table('module_rights')->where('id_module', Modules::CancelTransaction)->count());
    }

    public function test_one_cancellation_per_transaction_is_enforced_by_the_database(): void
    {
        $row = [
            'id_transaction' => 987654321,
            'reason' => 'Uji pembatalan ganda',
            'cancelled_by' => 1,
            'cancelled_at' => now(),
            'branch_id' => self::BRANCH_ID,
        ];

        DB::table('transaction_cancellation')->insert($row);

        $this->expectException(QueryException::class);
        DB::table('transaction_cancellation')->insert($row);
    }

    public function test_reversal_columns_and_indexes_exist(): void
    {
        $this->assertTrue(Schema::hasColumn('topup_history', 'reversal_of'));
        $this->assertTrue(Schema::hasColumn('savings_mutation', 'reversal_of'));

        $this->assertSame(['reversal_of'], $this->indexColumns('topup_history', 'topup_history_reversal_of_index'));
        $this->assertSame(['id_transaction'], $this->indexColumns('topup_history', 'topup_history_id_transaction_index'));
        $this->assertSame(['reversal_of'], $this->indexColumns('savings_mutation', 'savings_mutation_reversal_of_index'));
        $this->assertSame(['id_transaction', 'flag'], $this->indexColumns('savings_mutation', 'savings_mutation_id_transaction_flag_index'));
        $this->assertSame(['id_transaction'], $this->indexColumns('transaction_bill', 'transaction_bill_id_transaction_index'));
        $this->assertSame(['id_transaction'], $this->indexColumns('transaction_cancellation', 'transaction_cancellation_id_transaction_unique'));
        $this->assertSame(['cancelled_at'], $this->indexColumns('transaction_cancellation', 'transaction_cancellation_cancelled_at_index'));
        $this->assertSame(['branch_id'], $this->indexColumns('transaction_cancellation', 'transaction_cancellation_branch_id_index'));
    }

    /** @return array<int, string> kolom indeks berurutan */
    private function indexColumns(string $table, string $index): array
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))
            ->sortBy('Seq_in_index')
            ->pluck('Column_name')
            ->all();
    }

    public function test_feature_lang_keys_are_defined_once_and_old_message_is_intact(): void
    {
        $feature = '/^(cancel_|cancellation|cancelled|transaction_cancellation$|savings_deposit_cancellation$)/';

        foreach (['id', 'en'] as $lang) {
            foreach (['label', 'message', 'string'] as $file) {
                preg_match_all("/^    '([^']+)' =>/m", file_get_contents(lang_path("{$lang}/{$file}.php")), $m);
                $keys = array_filter($m[1], fn ($k) => preg_match($feature, $k));
                $dupes = array_keys(array_filter(array_count_values($keys), fn ($n) => $n > 1));

                $this->assertSame([], $dupes, "Kunci ganda di lang/{$lang}/{$file}.php");
            }
        }

        app()->setLocale('id');
        $this->assertSame('Tagihan berhasil dibatalkan', __('message.cancel_success', ['label' => 'Tagihan']));
    }
}
