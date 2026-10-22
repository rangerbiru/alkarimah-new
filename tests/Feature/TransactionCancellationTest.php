<?php

namespace Tests\Feature;

use App\Constants\Modules;
use App\Enums\TransactionFlag;
use App\Enums\TransactionMethod;
use App\Enums\TransactionStatus;
use App\Models\User;
use App\Services\CancellationException;
use App\Services\TransactionCancellation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use RuntimeException;
use Tests\Feature\Concerns\CancellationFixtures;

/**
 * Bagian B fitur Pembatalan Transaksi: service dan tiga reverser.
 */
class TransactionCancellationTest extends ModuleAccessTestCase
{
    use CancellationFixtures;

    private const REASON = 'Salah transfer oleh orang tua';

    private function cancel(int $id, string $reason = self::REASON, ?User $actor = null, ?TransactionCancellation $service = null): array
    {
        $actor ??= $this->admin;
        $this->actingAs($actor);

        return ($service ?? new TransactionCancellation)->cancel($id, $reason, $actor);
    }

    /** @return array<int, string> kode penolakan, atau [] bila berhasil dibatalkan */
    private function blockedCodes(int $id, ?User $actor = null): array
    {
        try {
            $this->cancel($id, self::REASON, $actor);
        } catch (CancellationException $e) {
            return $e->codes();
        }

        return [];
    }

    private function statusOf(int $id): string
    {
        return (string) DB::table('transaction')->where('id', $id)->value('status');
    }

    private function assertNothingWritten(int $id, array $expected_state): void
    {
        $this->assertEquals($expected_state, $this->state());
        $this->assertSame(TransactionStatus::Paid->value, $this->statusOf($id));
        $this->assertSame(0, DB::table('transaction_cancellation')->where('id_transaction', $id)->count());
        $this->assertSame(0, DB::table('topup_history')->where('id_transaction', $id)->whereNotNull('reversal_of')->count());
        $this->assertSame(0, DB::table('savings_mutation')->where('id_transaction', $id)->whereNotNull('reversal_of')->count());
    }

    // ---- pembalikan ----

    public function test_bill_payment_by_transfer_is_reversed_exactly(): void
    {
        $before = $this->state();
        $id = $this->payBillsByTransfer();
        $paid = $this->state();

        $this->assertNotEquals($before, $paid);
        $this->assertEquals(100484, $paid['parent_balance']);
        $this->assertSame(TransactionStatus::Paid->value, $this->statusOf($id));

        $result = $this->cancel($id);

        $this->assertEquals($before, $this->state());
        $this->assertSame(TransactionStatus::Cancelled->value, $this->statusOf($id));
        $this->assertSame($id, $result['id']);

        // Baris asli tidak berubah; masing-masing punya satu pembalik berlawanan
        $original = DB::table('topup_history')->where('id_transaction', $id)->whereNull('reversal_of')->get();
        $this->assertCount(1, $original);
        $this->assertEquals(484, $original[0]->debit);
        $this->assertEquals(0, $original[0]->credit);
        $reversal = DB::table('topup_history')->where('reversal_of', $original[0]->id)->get();
        $this->assertCount(1, $reversal);
        $this->assertEquals(0, $reversal[0]->debit);
        $this->assertEquals(484, $reversal[0]->credit);
        $this->assertEquals(100000, $reversal[0]->balance);
        $this->assertSame($id, (int) $reversal[0]->id_transaction);

        // Daftar tagihan di transaksi tetap ada, tagihannya sendiri sudah dilepas
        $this->assertEquals($this->tbIds, json_decode(DB::table('transaction')->where('id', $id)->value('bills')));
        $this->assertSame(0, DB::table('transaction_bill')->where('id_transaction', $id)->count());

        $row = DB::table('transaction_cancellation')->where('id_transaction', $id)->first();
        $this->assertSame(self::REASON, $row->reason);
        $this->assertSame($this->admin->id, (int) $row->cancelled_by);
        $this->assertSame($result['cancellation_id'], (int) $row->id);
        $this->assertCount(2, json_decode($row->snapshot, true)['plan']['bills']);
    }

    public function test_bill_payment_with_a_discount_gives_the_discount_back(): void
    {
        $this->addDiscount();
        $before = $this->state();
        $id = $this->payBillsByTransfer(discount: true);
        $paid = $this->state();

        $this->assertEquals(50000, DB::table('transaction')->where('id', $id)->value('discount'));
        $this->assertEquals(1500000, DB::table('transaction')->where('id', $id)->value('subtotal'));
        $this->assertNotEquals($before['discounts'], $paid['discounts']);

        $this->cancel($id);

        $this->assertEquals($before, $this->state());
        $this->assertSame('{"2026-10":0}', DB::table('bill_discount')->where('id_student', $this->studentId)->value('applies_to'));
    }

    #[RunInSeparateProcess]
    public function test_bill_payment_with_parent_balance_returns_the_balance(): void
    {
        DB::table('parent')->where('id', $this->parentId)->update(['balance' => 2000000]);
        $before = $this->state();

        $this->actingAs($this->ortu)->postJson(route('finance.payment.store'), [
            'bills' => [$this->tbIds[0] => 750000, $this->tbIds[1] => 750000],
            'id_student' => $this->studentId,
            'payment_method' => TransactionMethod::TopupBalance->value,
        ])->assertOk()->assertJsonPath('status', true);

        $id = (int) DB::table('transaction')->where('flag', TransactionFlag::Tagihan->value)->where('payment_method', TransactionMethod::TopupBalance->value)->max('id');
        $this->assertEquals(500000, $this->state()['parent_balance']);

        $this->cancel($id);

        $this->assertEquals($before, $this->state());
        $this->assertEquals(2000000, $this->state()['parent_balance']);
        $this->assertEquals(1500000, DB::table('topup_history')->where('id_transaction', $id)->whereNotNull('reversal_of')->value('debit'));
    }

    public function test_savings_deposit_is_reversed_with_its_unique_code(): void
    {
        $before = $this->state();
        $id = $this->paySavingsDeposit();
        $paid = $this->state();

        $this->assertEquals(150000, $paid['savings_balance']);
        $this->assertEquals(100161, $paid['parent_balance']);

        $this->cancel($id);

        $this->assertEquals($before, $this->state());
        $original = DB::table('savings_mutation')->where('id_transaction', $id)->where('flag', 1)->whereNull('reversal_of')->first();
        $reversal = DB::table('savings_mutation')->where('reversal_of', $original->id)->first();
        $this->assertSame(3, (int) $reversal->flag);
        $this->assertEquals(150000, $reversal->credit);
        $this->assertEquals(0, $reversal->debit);
        $this->assertEquals(0, $reversal->balance);
        $this->assertSame((int) $original->branch_id, (int) $reversal->branch_id);
        $this->assertEquals(150000, $original->debit);
    }

    public function test_balance_topup_is_reversed(): void
    {
        $before = $this->state();
        $id = $this->payTopup();

        $this->assertEquals(300000, $this->state()['parent_balance']);

        $this->cancel($id);

        $this->assertEquals($before, $this->state());
        $this->assertSame(1, DB::table('topup_history')->where('id_transaction', $id)->whereNotNull('reversal_of')->count());
    }

    // ---- pratinjau dan peringatan ----

    public function test_preview_only_reads(): void
    {
        $id = $this->payBillsByTransfer();
        $paid = $this->state();
        $counts = [DB::table('topup_history')->count(), DB::table('savings_mutation')->count(), DB::table('transaction_cancellation')->count()];
        $this->actingAs($this->admin);

        $preview = (new TransactionCancellation)->preview($id, $this->admin);

        $this->assertTrue($preview['can_cancel']);
        $this->assertSame([], $preview['blockers']);
        $this->assertSame('transfer', $preview['refund']['mode']);
        $this->assertEquals(1500484, $preview['refund']['amount']);
        $this->assertEquals(484, $preview['unique_code_reversed']);
        $this->assertCount(2, $preview['details']['bills']);
        $this->assertEquals(['before' => 100484, 'after' => 100000, 'delta' => -484], $preview['parent_balance']);
        $this->assertNull($preview['student_savings']);
        $this->assertSame('Santri Uji Batal', $preview['student']['name']);

        $this->assertEquals($paid, $this->state());
        $this->assertSame($counts, [DB::table('topup_history')->count(), DB::table('savings_mutation')->count(), DB::table('transaction_cancellation')->count()]);
        $this->assertSame(TransactionStatus::Paid->value, $this->statusOf($id));
    }

    public function test_previous_month_transaction_only_gets_a_warning(): void
    {
        $before = $this->state();
        $id = $this->payBillsByTransfer();
        $last_month = Carbon::now()->subMonthNoOverflow()->format('Y-m-d');

        DB::table('transaction')->where('id', $id)->update(['paid_at' => $last_month.' 10:00:00']);
        foreach (['report_bill_type', 'report_bill_method', 'report_bill_class'] as $table) {
            DB::table($table)->where('id_year', $this->yearId)->update(['dates' => $last_month]);
        }

        $preview = (new TransactionCancellation)->preview($id, $this->admin);
        $this->assertTrue($preview['can_cancel']);
        $this->assertSame(['month'], array_column($preview['warnings'], 'code'));

        $this->cancel($id);
        $this->assertEquals($before, $this->state());
    }

    // ---- penolakan ----

    #[DataProvider('invalidReasons')]
    public function test_invalid_reason_is_refused_and_nothing_changes(string $reason): void
    {
        $id = $this->payBillsByTransfer();
        $paid = $this->state();

        try {
            $this->cancel($id, $reason);
            $this->fail('CancellationException diharapkan');
        } catch (CancellationException $e) {
            $this->assertSame(['reason'], $e->codes());
        }

        $this->assertNothingWritten($id, $paid);
    }

    public static function invalidReasons(): array
    {
        return ['kosong' => [''], 'spasi saja' => ['    '], 'dua kata' => ['salah transfer'], 'kata banyak tapi pendek' => ['a b c']];
    }

    public function test_transaction_already_in_a_cash_deposit_is_refused(): void
    {
        $id = $this->payBillsByTransfer();
        DB::table('transaction')->where('id', $id)->update(['status_deposit' => 1]);
        $deposit = ['dates' => '2026-10-20', 'total' => 1500000, 'status' => 0, 'branch_id' => self::BRANCH_ID, 'created_by' => 0];
        // LIKE menjaring dua-duanya, tetapi hanya setoran yang benar-benar memuat id ini yang disebut
        DB::table('cash_deposit')->insert(['number' => 'SK-SALAH', 'transactions' => json_encode([(string) ($id * 10)])] + $deposit);
        DB::table('cash_deposit')->insert(['number' => 'SK-BENAR', 'transactions' => json_encode(['1', (string) $id])] + $deposit);
        $paid = $this->state();

        try {
            $this->cancel($id);
            $this->fail('CancellationException diharapkan');
        } catch (CancellationException $e) {
            $this->assertSame(['cash_deposit'], $e->codes());
            $this->assertStringContainsString('SK-BENAR', $e->getMessage());
            $this->assertStringNotContainsString('SK-SALAH', $e->getMessage());
        }

        $this->assertNothingWritten($id, $paid);
    }

    public function test_unique_code_already_deposited_is_refused(): void
    {
        $id = $this->payBillsByTransfer();
        DB::table('transaction')->where('id', $id)->update(['status_deposit_code' => 1]);
        DB::table('unique_code_deposit')->insert([
            'number' => 'KU-001', 'dates' => '2026-10-20', 'transactions' => json_encode([(string) $id]), 'total' => 484,
            'status' => 0, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);
        $paid = $this->state();

        try {
            $this->cancel($id);
            $this->fail('CancellationException diharapkan');
        } catch (CancellationException $e) {
            $this->assertSame(['code_deposit'], $e->codes());
            $this->assertStringContainsString('KU-001', $e->getMessage());
        }

        $this->assertNothingWritten($id, $paid);
    }

    public function test_parent_balance_already_spent_is_refused(): void
    {
        $id = $this->payTopup();
        DB::table('parent')->where('id', $this->parentId)->update(['balance' => 50000]);
        $paid = $this->state();

        try {
            $this->cancel($id);
            $this->fail('CancellationException diharapkan');
        } catch (CancellationException $e) {
            $this->assertSame(['balance_parent'], $e->codes());
            $this->assertStringContainsString('50.000', $e->getMessage());
            $this->assertStringContainsString('200.000', $e->getMessage());
        }

        $this->assertNothingWritten($id, $paid);
    }

    public function test_student_savings_already_withdrawn_is_refused(): void
    {
        $id = $this->paySavingsDeposit();
        DB::table('student')->where('id', $this->studentId)->update(['balance_savings' => 0]);
        $paid = $this->state();

        $this->assertSame(['balance_student'], $this->blockedCodes($id));
        $this->assertNothingWritten($id, $paid);
    }

    public function test_cancelling_twice_changes_nothing_the_second_time(): void
    {
        $id = $this->payBillsByTransfer();
        $this->cancel($id);
        $after = $this->state();
        $rows = [DB::table('topup_history')->count(), DB::table('transaction_cancellation')->count()];

        $this->assertSame(['already_cancelled'], $this->blockedCodes($id));

        $this->assertEquals($after, $this->state());
        $this->assertSame($rows, [DB::table('topup_history')->count(), DB::table('transaction_cancellation')->count()]);
    }

    public function test_transactions_outside_the_scope_are_refused(): void
    {
        $this->actingAs($this->kasir);
        $cash = $this->createPending([
            'id_student' => $this->studentId, 'flag' => TransactionFlag::Tagihan->value, 'subtotal' => 1000, 'total' => 1000,
            'payment_method' => TransactionMethod::Cash->value, 'status' => TransactionStatus::Paid->value, 'paid_at' => now(),
        ]);
        $pending = $this->createPending([
            'id_parent' => $this->parentId, 'flag' => TransactionFlag::TopupSaldo->value, 'subtotal' => 1000, 'total' => 1000,
            'payment_method' => TransactionMethod::BSI->value,
        ]);

        $this->assertSame(['scope'], $this->blockedCodes($cash));
        $this->assertSame(['not_paid'], $this->blockedCodes($pending));
        $this->assertSame(['not_found'], $this->blockedCodes(987654321));
    }

    public function test_incomplete_ledger_is_refused_instead_of_guessed(): void
    {
        $id = $this->payBillsByTransfer();
        DB::table('topup_history')->where('id_transaction', $id)->delete();
        $paid = $this->state();

        $this->assertSame(['ledger'], $this->blockedCodes($id));
        $this->assertNothingWritten($id, $paid);
    }

    public function test_missing_report_row_is_refused(): void
    {
        $id = $this->payBillsByTransfer();
        DB::table('report_bill_method')->where('id_year', $this->yearId)->delete();
        $paid = $this->state();

        $this->assertSame(['report_rows'], $this->blockedCodes($id));
        $this->assertNothingWritten($id, $paid);
    }

    public function test_student_who_changed_class_after_payment_is_refused(): void
    {
        $id = $this->payBillsByTransfer();
        $this->moveStudentToAnotherClass();
        $paid = $this->state();

        $this->assertSame(['class_changed'], $this->blockedCodes($id));
        $this->assertNothingWritten($id, $paid);
    }

    public function test_class_change_is_ignored_for_deposits_that_do_not_depend_on_class(): void
    {
        $id = $this->paySavingsDeposit();
        $this->moveStudentToAnotherClass();

        $this->assertSame([], $this->blockedCodes($id));
    }

    private function logStudentUpdate(array $attributes, array $old): void
    {
        DB::table('activity_log')->insert([
            'log_name' => 'default', 'description' => 'updated', 'event' => 'updated',
            'subject_type' => \App\Models\Student::class, 'subject_id' => $this->studentId,
            'properties' => json_encode(['attributes' => $attributes, 'old' => $old]),
            'created_at' => now()->addMinute(), 'updated_at' => now()->addMinute(),
        ]);
    }

    public function test_partial_student_update_log_is_not_a_class_change(): void
    {
        // Update saldo tabungan mencatat model yang dipilih sebagian: old.id_class = null
        $id = $this->payBillsByTransfer();
        $this->logStudentUpdate(['id_class' => $this->classId, 'balance_savings' => 150000], ['id_class' => null, 'balance_savings' => 0]);

        $this->assertSame([], $this->blockedCodes($id));
    }

    public function test_activity_log_class_change_is_refused(): void
    {
        $id = $this->payBillsByTransfer();
        $this->logStudentUpdate(['id_class' => $this->classId + 999], ['id_class' => $this->classId]);
        $paid = $this->state();

        $this->assertSame(['class_changed'], $this->blockedCodes($id));
        $this->assertNothingWritten($id, $paid);
    }

    private function moveStudentToAnotherClass(): void
    {
        DB::table('student_displacement_history')->insert([
            'id_student' => $this->studentId, 'before_class_id' => $this->classId, 'before_nis' => 2489,
            'after_class_id' => $this->classId + 999, 'after_nis' => 2489,
            'branch_id' => self::BRANCH_ID, 'created_by' => 0, 'created_at' => now()->addMinute(),
        ]);
    }

    // ---- hak akses ----

    public function test_only_admin_or_employee_with_the_module_and_without_cashier_modules_may_cancel(): void
    {
        $service = new TransactionCancellation;

        $this->assertTrue($service->allowed($this->admin));
        $this->assertTrue($service->allowed($this->createUser('pegawai', [Modules::CancelTransaction])));
        $this->assertTrue($service->allowed($this->createUser('pegawai', [Modules::CancelTransaction, Modules::Bendahara])));

        $this->assertFalse($service->allowed(null));
        $this->assertFalse($service->allowed($this->createUser('pegawai')));
        $this->assertFalse($service->allowed($this->createUser('pegawai', [Modules::Kasir])));
        // kasir yang diberi modul pembatalan tetap ditolak: pegawai bermodul kasir / kasir unit, dan role kasir
        $this->assertFalse($service->allowed($this->createUser('pegawai', [Modules::CancelTransaction, Modules::Kasir])));
        $this->assertFalse($service->allowed($this->createUser('pegawai', [Modules::CancelTransaction, Modules::KasirUnit])));
        $this->assertFalse($service->allowed($this->createUser('kasir', [Modules::CancelTransaction])));
        $this->assertFalse($service->allowed($this->createUser('bendahara', [Modules::CancelTransaction])));
        $this->assertFalse($service->allowed($this->ortu));
    }

    public function test_user_without_access_cannot_cancel_or_preview(): void
    {
        $id = $this->payBillsByTransfer();
        $paid = $this->state();

        foreach ([$this->kasir, $this->createUser('kasir', [Modules::CancelTransaction]), $this->createUser('pegawai'), $this->ortu] as $user) {
            $this->assertSame(['forbidden'], $this->blockedCodes($id, $user));

            try {
                $this->actingAs($user);
                (new TransactionCancellation)->preview($id, $user);
                $this->fail('CancellationException diharapkan');
            } catch (CancellationException $e) {
                $this->assertSame(['forbidden'], $e->codes());
            }
        }

        $this->assertNothingWritten($id, $paid);
    }

    public function test_employee_with_the_module_can_cancel(): void
    {
        $id = $this->payBillsByTransfer();

        $this->cancel($id, self::REASON, $this->createUser('pegawai', [Modules::CancelTransaction]));

        $this->assertSame(TransactionStatus::Cancelled->value, $this->statusOf($id));
    }

    public function test_admin_cannot_cancel_the_transaction_of_their_own_child(): void
    {
        $id = $this->payBillsByTransfer();
        $paid = $this->state();

        // dari akun: baris orang tua milik admin ini
        DB::table('parent')->where('id', $this->parentId)->update(['id_user' => $this->admin->id]);
        $this->assertSame(['own_child'], $this->blockedCodes($id));
        $this->assertNothingWritten($id, $paid);

        // dari nomor HP: format berbeda, 10 digit terakhir sama
        DB::table('parent')->where('id', $this->parentId)->update(['id_user' => $this->ortu->id]);
        DB::table('users')->where('id', $this->admin->id)->update(['phone' => '+62 812-3456-7890']);
        $this->assertSame(['own_child'], $this->blockedCodes($id, User::withoutGlobalScopes()->find($this->admin->id)));
        $this->assertNothingWritten($id, $paid);
    }

    // ---- atomik ----

    #[DataProvider('steps')]
    public function test_failure_at_any_step_leaves_nothing_behind(string $failAt): void
    {
        $this->addDiscount();
        $id = $this->payBillsByTransfer(discount: true);
        $paid = $this->state();

        $service = new class($failAt) extends TransactionCancellation
        {
            public function __construct(private string $failAt) {}

            protected function step(string $name): void
            {
                if ($name === $this->failAt) {
                    throw new RuntimeException('gagal di tahap '.$name);
                }
            }
        };

        try {
            $this->cancel($id, self::REASON, null, $service);
            $this->fail('RuntimeException diharapkan');
        } catch (RuntimeException $e) {
            $this->assertSame('gagal di tahap '.$failAt, $e->getMessage());
        }

        $this->assertNothingWritten($id, $paid);
    }

    public static function steps(): array
    {
        return array_combine($names = ['checked', 'bills', 'discounts', 'reports', 'ledger', 'balances', 'status'], array_map(fn ($n) => [$n], $names));
    }
}
