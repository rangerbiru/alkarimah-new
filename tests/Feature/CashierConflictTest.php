<?php

namespace Tests\Feature;

use App\Constants\Modules;
use App\Events\SavingsDepositPaid;
use App\Events\TransactionBillPaid;
use App\Models\CashDeposit;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CashierConflict;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Konflik kepentingan kasir: siapa pun yang menjalankan aksi kasir tidak boleh memproses tagihan,
 * pembayaran, atau tabungan anaknya sendiri; santri lain tetap bisa diproses.
 */
class CashierConflictTest extends ModuleAccessTestCase
{
    private User $kasir;

    private int $ownParent;

    private int $otherParent;

    private int $ownStudent;

    private int $otherStudent;

    private array $bill = [];

    private array $tx = [];

    private array $withdrawal = [];

    private int $billTemplate;

    protected function setUp(): void
    {
        parent::setUp();

        $year = DB::table('year')->insertGetId([
            'start_year' => 2026, 'start_month' => '07', 'end_year' => 2027, 'end_month' => '06',
            'status' => 1, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);
        $type = DB::table('bill_type')->insertGetId([
            'name' => 'SPP Uji', 'period' => 2, 'spp' => 1, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);
        $this->billTemplate = DB::table('bill')->insertGetId([
            'id_year' => $year, 'id_type' => $type, 'name' => 'SPP Uji', 'nominal' => 100000,
            'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);
        DB::table('report_bill')->insert([
            'id_year' => $year, 'id_type' => $type, 'level' => 3, 'total' => 400000,
            'paid' => 0, 'remaining' => 400000, 'branch_id' => self::BRANCH_ID,
        ]);
        $class = DB::table('class')->insertGetId([
            'id_wali_kelas' => $this->createUser('wali-kelas')->id, 'name' => 'Kelas Uji', 'level_education' => 'sd',
            'level_class' => 1, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);

        // Kasir yang juga orang tua: baris orang tuanya terhubung lewat id_user
        $this->kasir = $this->createUser('kasir');
        $this->createEmployee($this->kasir);
        $this->ownParent = $this->parentRow($this->kasir->id, 'Ortu Kasir', '081200000000');
        $this->otherParent = $this->parentRow(null, 'Ortu Lain', '081211111111');

        foreach (['own' => $this->ownParent, 'other' => $this->otherParent] as $key => $parentId) {
            $student = DB::table('student')->insertGetId([
                'id_parent' => $parentId, 'id_class' => $class, 'nis' => 'C-'.$key, 'name' => 'Siswa '.$key,
                'gender' => 'male', 'religion' => 'Islam', 'exculs' => '[]', 'status' => 1, 'balance_savings' => 10000,
                'branch_id' => self::BRANCH_ID, 'created_by' => 0,
            ]);
            $key === 'own' ? $this->ownStudent = $student : $this->otherStudent = $student;

            $this->bill[$key] = DB::table('transaction_bill')->insertGetId([
                'id_student' => $student, 'id_bill' => $this->billTemplate, 'months' => 7, 'years' => 2026,
                'total' => 100000, 'status' => 0, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
            ]);
            $this->withdrawal[$key] = DB::table('savings_withdrawal')->insertGetId([
                'id_student' => $student, 'number' => 'W-'.$key, 'dates' => date('Y-m-d'), 'total' => 5000,
                'branch_id' => self::BRANCH_ID, 'created_by' => 0,
            ]);
            // transaksi tagihan lunas (kandidat setoran kas) dan transaksi tagihan menunggu (pending)
            $this->tx[$key] = $this->transaction(1, ['id_student' => $student, 'id_parent' => $parentId]);
            $this->tx[$key.'_code'] = $this->transaction(1, ['id_student' => $student, 'unique_code' => 123, 'total' => 100123]);
            $this->tx[$key.'_pending'] = $this->transaction(1, [
                'id_student' => $student, 'id_parent' => $parentId, 'status' => 0, 'paid_at' => null, 'paid_by' => null,
                'bills' => json_encode([$this->bill[$key]]), 'payment_method' => 2,
            ]);
        }
    }

    private function parentRow(?int $userId, string $name, string $phone): int
    {
        return DB::table('parent')->insertGetId([
            'id_user' => $userId, 'name' => $name, 'phone' => $phone, 'gender' => 'male', 'balance' => 50000,
            'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);
    }

    private function transaction(int $flag, array $attrs = []): int
    {
        static $n = 0;
        $n++;

        return DB::table('transaction')->insertGetId(array_merge([
            'number' => 'CC-'.$n.'-'.random_int(1000, 9999), 'dates' => date('Y-m-d'),
            'subtotal' => 100000, 'total' => 100000, 'flag' => $flag, 'payment_method' => 1,
            'status' => 1, 'paid_at' => date('Y-m-d H:i:s'), 'paid_by' => 0,
            'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ], $attrs));
    }

    private function conflictLogs(string $action): int
    {
        return DB::table('activity_log')->where('log_name', 'cashier-conflict')->where('properties->action', $action)->count();
    }

    private function deposit(array $keys): array
    {
        $today = date('Y-m-d');

        return [
            'start_date' => $today, 'end_date' => $today, 'dates' => $today,
            'transaction' => collect($keys)->map(fn ($k) => $this->tx[$k])->all(),
        ];
    }

    // ---------- service ----------

    public function test_own_students_are_found_by_relation_and_by_last_ten_digits_of_the_phone(): void
    {
        $this->assertSame([$this->ownStudent], CashierConflict::ownStudentIds($this->kasir));

        // cadangan: tanpa relasi, 10 digit terakhir nomor HP (format berbeda)
        $phoneOnly = $this->createUser('kasir');
        DB::table('users')->where('id', $phoneOnly->id)->update(['phone' => '081233334444']);
        $parent = $this->parentRow(null, 'Ortu Nomor Sama', '+62 812-3333-4444');
        $student = DB::table('student')->insertGetId([
            'id_parent' => $parent, 'id_class' => 1, 'nis' => 'C-phone', 'name' => 'Siswa phone', 'gender' => 'male', 'religion' => 'Islam',
            'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);
        $this->assertSame([$student], CashierConflict::ownStudentIds(User::withoutGlobalScopes()->find($phoneOnly->id)));

        // HP kosong / pendek: fallback dilewati
        foreach (['', '0', '0812'] as $phone) {
            DB::table('users')->where('id', $phoneOnly->id)->update(['phone' => $phone]);
            $this->assertSame([], CashierConflict::ownStudentIds(User::withoutGlobalScopes()->find($phoneOnly->id)));
        }
    }

    // ---------- bayar tunai ----------

    private function pay(User $as, string $student, array $billKeys = null)
    {
        $keys = $billKeys ?? [$student];

        return $this->actingAs($as)->postJson(route('finance.transaction.store'), [
            'id_student' => $this->{$student.'Student'}, 'dates' => date('Y-m-d'), 'payment_method' => '1',
            'bills' => collect($keys)->mapWithKeys(fn ($k) => [$this->bill[$k] => 100000])->all(),
        ]);
    }

    public function test_cashier_cannot_pay_the_bill_of_their_own_child_but_can_pay_others(): void
    {
        Event::fake([TransactionBillPaid::class]);

        $this->pay($this->kasir, 'own')->assertForbidden()->assertJson(['status' => false, 'message' => __('message.cashier_conflict')]);
        $this->assertDatabaseHas('transaction_bill', ['id' => $this->bill['own'], 'status' => 0]);
        $this->assertSame(0, DB::table('transaction')->where('paid_by', $this->kasir->id)->count());
        $this->assertSame(1, $this->conflictLogs('bill-payment'));

        $log = DB::table('activity_log')->where('log_name', 'cashier-conflict')->first();
        $this->assertSame($this->kasir->id, (int) $log->causer_id);
        $this->assertSame([$this->ownStudent], json_decode($log->properties, true)['student_ids']);

        $this->pay($this->kasir, 'other')->assertOk()->assertJson(['status' => true]);
        $this->assertDatabaseHas('transaction_bill', ['id' => $this->bill['other'], 'status' => 1]);
    }

    public function test_the_child_bill_cannot_be_smuggled_in_through_another_student(): void
    {
        Event::fake([TransactionBillPaid::class]);

        $this->pay($this->kasir, 'other', ['other', 'own'])->assertForbidden();

        $this->assertDatabaseHas('transaction_bill', ['id' => $this->bill['own'], 'status' => 0]);
        $this->assertDatabaseHas('transaction_bill', ['id' => $this->bill['other'], 'status' => 0]);
    }

    public function test_paying_from_the_parent_balance_for_own_child_is_refused_too(): void
    {
        $payload = [
            'id_student' => $this->ownStudent, 'dates' => date('Y-m-d'), 'payment_method' => 'balance',
            'bills' => [$this->bill['own'] => 100000],
        ];

        $this->actingAs($this->kasir)->postJson(route('finance.transaction.store'), $payload)->assertForbidden();
        $this->assertEquals(50000, DB::table('parent')->where('id', $this->ownParent)->value('balance'));
    }

    public function test_the_rule_also_applies_to_a_pegawai_holding_the_kasir_module_and_kasir_unit(): void
    {
        Event::fake([TransactionBillPaid::class]);

        foreach ([$this->createUser('pegawai', [Modules::Kasir]), $this->createUser('kasir-unit')] as $user) {
            $this->createEmployee($user);
            DB::table('parent')->where('id', $this->ownParent)->update(['id_user' => $user->id]);

            $this->pay($user, 'own')->assertForbidden();
            $this->pay($user, 'other')->assertOk();
            DB::table('transaction_bill')->where('id', $this->bill['other'])->update(['status' => 0]);
        }
    }

    public function test_the_pay_form_only_warns(): void
    {
        $search = fn ($nis) => $this->actingAs($this->kasir)->postJson(route('finance.transaction.get.bill'), ['search' => $nis.' - Siswa '.substr($nis, 2)])
            ->assertOk()->json('data');

        $this->assertTrue($search('C-own')['conflict']);
        $this->assertSame($this->ownStudent, $search('C-own')['student']);
        $this->assertFalse($search('C-other')['conflict']);
    }

    // ---------- approve / batal transaksi pending ----------

    public function test_pending_transaction_of_own_child_cannot_be_approved_or_cancelled(): void
    {
        Event::fake([TransactionBillPaid::class]);
        $id = fn ($key) => Transaction::withoutGlobalScopes()->find($this->tx[$key])->encrypted_id;

        foreach (['paid' => 'pending-approve', 'cancel' => 'pending-cancel'] as $status => $action) {
            $this->actingAs($this->kasir)->postJson(route('finance.transaction.update.status'), ['id' => $id('own_pending'), 'status' => $status, 'unique_code_status' => '1'])
                ->assertForbidden()->assertJson(['status' => false]);
            $this->assertSame(1, $this->conflictLogs($action));
        }

        $this->assertDatabaseHas('transaction', ['id' => $this->tx['own_pending'], 'status' => 0, 'deleted_at' => null]);
        $this->assertDatabaseHas('transaction_bill', ['id' => $this->bill['own'], 'status' => 0]);

        $this->actingAs($this->kasir)->postJson(route('finance.transaction.update.status'), ['id' => $id('other_pending'), 'status' => 'paid', 'unique_code_status' => '1'])
            ->assertOk()->assertJson(['status' => true]);
        $this->assertDatabaseHas('transaction', ['id' => $this->tx['other_pending'], 'status' => 1]);
    }

    // ---------- setor / edit / hapus kas ----------

    public function test_cash_deposit_with_a_transaction_of_own_child_is_refused_atomically_and_logged(): void
    {
        $this->actingAs($this->kasir)->postJson(route('finance.transaction.store.cash'), $this->deposit(['other', 'own']))
            ->assertForbidden()->assertJson(['status' => false]);

        $this->assertDatabaseHas('transaction', ['id' => $this->tx['other'], 'status_deposit' => 0]);
        $this->assertDatabaseHas('transaction', ['id' => $this->tx['own'], 'status_deposit' => 0]);
        $this->assertSame(0, DB::table('cash_deposit')->count());
        // dicatat walau transaksi database dibatalkan
        $this->assertSame(1, $this->conflictLogs('cash-deposit-store'));

        $this->actingAs($this->kasir)->postJson(route('finance.transaction.store.cash'), $this->deposit(['other']))->assertOk()->assertJson(['status' => true]);
        $this->assertDatabaseHas('transaction', ['id' => $this->tx['other'], 'status_deposit' => 1]);
    }

    public function test_cash_deposit_check_runs_inside_the_transaction_after_locking(): void
    {
        $queries = [];
        DB::listen(function ($q) use (&$queries) {
            $queries[] = $q->sql;
        });

        $this->actingAs($this->kasir)->postJson(route('finance.transaction.store.cash'), $this->deposit(['own']))->assertForbidden();

        $lock = collect($queries)->search(fn ($sql) => str_contains($sql, 'from `transaction`') && str_contains($sql, 'for update'));
        $this->assertNotFalse($lock, 'baris transaksi dikunci dengan lockForUpdate sebelum pemeriksaan');
    }

    public function test_edit_and_delete_of_a_cash_deposit_holding_own_child_transactions_are_refused(): void
    {
        $this->actingAs($this->kasir)->postJson(route('finance.transaction.store.cash'), $this->deposit(['other']))->assertOk();
        $deposit = CashDeposit::withoutGlobalScopes()->firstOrFail();

        // menambah transaksi anak sendiri
        $this->actingAs($this->kasir)->postJson(route('finance.transaction.update.cash', $deposit->encrypted_id), $this->deposit(['other', 'own']))->assertForbidden();
        $this->assertDatabaseHas('transaction', ['id' => $this->tx['own'], 'status_deposit' => 0]);

        // setoran lama yang memuat transaksi anak sendiri: tidak bisa diedit maupun dihapus kasir ini
        DB::table('cash_deposit')->where('id', $deposit->id)->update(['transactions' => json_encode([$this->tx['own']])]);
        DB::table('transaction')->where('id', $this->tx['own'])->update(['status_deposit' => 1]);
        DB::table('transaction')->where('id', $this->tx['other'])->update(['status_deposit' => 0]);

        $this->actingAs($this->kasir)->postJson(route('finance.transaction.update.cash', $deposit->encrypted_id), $this->deposit(['other']))->assertForbidden();
        $this->actingAs($this->kasir)->deleteJson(route('finance.transaction.destroy.cash', $deposit->encrypted_id))->assertForbidden();

        $this->assertDatabaseHas('cash_deposit', ['id' => $deposit->id]);
        $this->assertDatabaseHas('transaction', ['id' => $this->tx['own'], 'status_deposit' => 1]);
        $this->assertSame(2, $this->conflictLogs('cash-deposit-update'));
        $this->assertSame(1, $this->conflictLogs('cash-deposit-destroy'));
    }

    public function test_unique_code_deposit_is_refused_for_own_child_transactions(): void
    {
        $payload = fn ($keys) => $this->deposit($keys);

        $this->actingAs($this->kasir)->postJson(route('finance.transaction.store.unique-code'), $payload(['own_code']))->assertForbidden();
        $this->assertDatabaseHas('transaction', ['id' => $this->tx['own_code'], 'status_deposit_code' => 0]);
        $this->assertSame(1, $this->conflictLogs('unique-code-deposit-store'));

        $this->actingAs($this->kasir)->postJson(route('finance.transaction.store.unique-code'), $payload(['other_code']))->assertOk();
        $deposit = \App\Models\UniqueCodeDeposit::withoutGlobalScopes()->firstOrFail();

        DB::table('unique_code_deposit')->where('id', $deposit->id)->update(['transactions' => json_encode([$this->tx['own_code']])]);
        $this->actingAs($this->kasir)->deleteJson(route('finance.transaction.destroy.unique-code', $deposit->encrypted_id))->assertForbidden();
        $this->assertDatabaseHas('unique_code_deposit', ['id' => $deposit->id]);
    }

    // ---------- tabungan ----------

    public function test_cashier_cannot_record_a_cash_savings_deposit_for_own_child(): void
    {
        Event::fake([SavingsDepositPaid::class]);
        $payload = fn ($student) => ['id_student' => $student, 'dates' => date('Y-m-d'), 'nominal' => '5.000'];

        $this->actingAs($this->kasir)->postJson(route('finance.savings.store'), $payload($this->ownStudent))->assertForbidden();
        $this->assertDatabaseMissing('transaction', ['id_student' => $this->ownStudent, 'flag' => 2, 'total' => 5000]);
        $this->assertSame(1, $this->conflictLogs('savings-deposit-cash'));

        $this->actingAs($this->kasir)->postJson(route('finance.savings.store'), $payload($this->otherStudent))->assertOk()->assertJson(['status' => true]);
        $this->assertDatabaseHas('transaction', ['id_student' => $this->otherStudent, 'flag' => 2, 'total' => 5000, 'status' => 1]);
    }

    public function test_savings_deposit_by_the_parent_for_own_child_is_still_allowed(): void
    {
        Event::fake([SavingsDepositPaid::class]);

        // form orang tua: mengirim payment_method dan kode unik; menghasilkan setoran menunggu (belum lunas) yang kedaluwarsa
        $code = 1234;
        DB::table('transaction_payment_code')->insert(['code' => $code, 'expired_at' => now()->addDay(), 'flag' => 2, 'status' => 0, 'created_by' => $this->kasir->id]);

        $this->actingAs($this->kasir)->postJson(route('finance.savings.store'), [
            'id_student' => $this->ownStudent, 'nominal' => '5.000', 'payment_method' => '2', 'unique_code' => $code,
        ])->assertOk()->assertJson(['status' => true]);

        $row = DB::table('transaction')->where('id_student', $this->ownStudent)->where('flag', 2)->where('subtotal', 5000)->first();
        $this->assertNotNull($row);
        $this->assertSame(0, (int) $row->status);
        $this->assertNotNull($row->expired_at);
        $this->assertSame(0, $this->conflictLogs('savings-deposit-cash'));
    }

    public function test_savings_withdrawal_of_own_child_is_refused_but_others_are_processed(): void
    {
        $payload = fn ($key) => [
            'id_parent' => 0, 'dates' => date('Y-m-d'), 'total' => 5000,
            'bills' => [$this->withdrawal[$key] => 5000],
        ];

        $this->actingAs($this->kasir)->postJson(route('finance.savings.process.withdrawal'), $payload('own'))->assertForbidden();
        $this->assertDatabaseMissing('transaction', ['flag' => 3, 'paid_by' => $this->kasir->id]);
        $this->assertSame(1, $this->conflictLogs('savings-withdrawal-process'));

        // campuran: semuanya ditolak
        $mixed = $payload('other');
        $mixed['bills'][$this->withdrawal['own']] = 5000;
        $this->actingAs($this->kasir)->postJson(route('finance.savings.process.withdrawal'), $mixed)->assertForbidden();
        $this->assertDatabaseMissing('transaction', ['flag' => 3, 'paid_by' => $this->kasir->id]);

        Event::fake([\App\Events\SavingsWithdrawalProcessed::class]);
        $this->actingAs($this->kasir)->postJson(route('finance.savings.process.withdrawal'), $payload('other'))->assertOk()->assertJson(['status' => true]);
    }

    public function test_penanggung_jawab_tabungan_cannot_manage_withdrawals_of_own_child(): void
    {
        $pj = $this->createUser('penanggung-jawab-tabungan');
        DB::table('parent')->where('id', $this->ownParent)->update(['id_user' => $pj->id]);
        $own = \App\Models\SavingsWithdrawal::withoutGlobalScopes()->find($this->withdrawal['own']);
        DB::table('setting')->insert(['branch_id' => self::BRANCH_ID]);

        $this->actingAs($pj)->deleteJson(route('finance.savings.destroy.withdrawal', $own->encrypted_id))->assertForbidden();
        $this->assertDatabaseHas('savings_withdrawal', ['id' => $own->id, 'deleted_at' => null]);

        $this->actingAs($pj)->postJson(route('finance.savings.store.withdrawal'), [
            'id_student' => $this->ownStudent, 'dates' => date('Y-m-d'), 'total' => 1000,
        ])->assertForbidden();
        $this->assertEquals(10000, DB::table('student')->where('id', $this->ownStudent)->value('balance_savings'));
    }

    // ---------- saldo orang tua ----------

    public function test_balance_topup_and_withdrawal_for_own_parent_are_refused(): void
    {
        $this->actingAs($this->kasir)->postJson(route('finance.balance.store.cash'), ['id_parent' => $this->ownParent, 'nominal' => '10.000', 'dates' => date('Y-m-d')])->assertForbidden();
        $this->actingAs($this->kasir)->postJson(route('finance.balance.store.withdrawal'), ['id_parent' => $this->ownParent, 'nominal' => '10.000'])->assertForbidden();
        $this->assertEquals(50000, DB::table('parent')->where('id', $this->ownParent)->value('balance'));
        $this->assertSame(1, $this->conflictLogs('balance-topup-cash'));
        $this->assertSame(1, $this->conflictLogs('balance-withdrawal'));

        $this->actingAs($this->kasir)->postJson(route('finance.balance.store.cash'), ['id_parent' => $this->otherParent, 'nominal' => '10.000', 'dates' => date('Y-m-d')])
            ->assertOk()->assertJson(['status' => true]);
        $this->assertEquals(60000, DB::table('parent')->where('id', $this->otherParent)->value('balance'));
    }

    // ---------- hapus tagihan ----------

    public function test_deleting_a_bill_of_own_child_is_refused(): void
    {
        $this->actingAs($this->kasir)->postJson(route('finance.bill.destroy'), ['student' => $this->ownStudent, 'bill' => $this->billTemplate, 'year' => 1])->assertForbidden();
        $this->assertDatabaseHas('transaction_bill', ['id' => $this->bill['own']]);
        $this->assertSame(1, $this->conflictLogs('bill-destroy'));
    }

    public function test_deleting_a_bill_setup_that_covers_own_child_is_refused(): void
    {
        $setup = \App\Models\Bill::withoutGlobalScopes()->find($this->billTemplate);

        $this->actingAs($this->kasir)->deleteJson(route('finance.bill.setup.destroy', $setup->encrypted_id))->assertForbidden();
        $this->assertDatabaseHas('bill', ['id' => $this->billTemplate, 'deleted_at' => null]);
        $this->assertDatabaseHas('transaction_bill', ['id' => $this->bill['own']]);
    }

    // ---------- kasir biasa tidak berubah ----------

    public function test_cashier_without_children_is_unaffected(): void
    {
        Event::fake([TransactionBillPaid::class]);
        $plain = $this->createUser('kasir');

        $this->pay($plain, 'own')->assertOk()->assertJson(['status' => true]);
        $this->actingAs($plain)->postJson(route('finance.transaction.store.cash'), $this->deposit(['other']))->assertOk();
        $this->assertSame(0, DB::table('activity_log')->where('log_name', 'cashier-conflict')->count());
    }
}
