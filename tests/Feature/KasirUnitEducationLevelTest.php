<?php

namespace Tests\Feature;

use App\Constants\Modules;
use App\Events\SavingsDepositPaid;
use App\Events\TransactionBillPaid;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Pembatasan jenjang untuk Kasir Unit: akun kasir-unit lama (jenjang tanpa modul) dan
 * pegawai pemegang modul Kasir Unit. Kasir biasa dan modul Kasir tetap tanpa batas.
 *
 * Fixture: dua jenjang (SD dan SMP), tiga orang tua (anak SD, anak SMP, anak SD+SMP).
 */
class KasirUnitEducationLevelTest extends ModuleAccessTestCase
{
    private array $class = [];

    private array $student = [];

    private array $parent = [];

    private array $bill = [];

    private array $tx = [];

    private array $withdrawal = [];

    private int $yearId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->yearId = DB::table('year')->insertGetId([
            'start_year' => 2026, 'start_month' => '07', 'end_year' => 2027, 'end_month' => '06',
            'status' => 1, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);
        $typeId = DB::table('bill_type')->insertGetId([
            'name' => 'SPP Test', 'period' => 2, 'spp' => 1, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);
        $billId = DB::table('bill')->insertGetId([
            'id_year' => $this->yearId, 'id_type' => $typeId, 'name' => 'SPP Test', 'nominal' => 100000,
            'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);

        $wali = $this->createUser('wali-kelas');

        foreach (['sd', 'smp'] as $level) {
            $this->class[$level] = DB::table('class')->insertGetId([
                'id_wali_kelas' => $wali->id, 'name' => 'Kelas '.strtoupper($level), 'level_education' => $level,
                'level_class' => 1, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
            ]);
        }

        // report_bill: sd.1 = level 3, smp.1 = level 9
        foreach ([3 => 100, 9 => 900] as $level => $total) {
            DB::table('report_bill')->insert([
                'id_year' => $this->yearId, 'id_type' => $typeId, 'level' => $level, 'total' => $total,
                'paid' => 0, 'remaining' => $total, 'branch_id' => self::BRANCH_ID,
            ]);
        }

        foreach (['sd', 'smp', 'both'] as $key) {
            $this->parent[$key] = DB::table('parent')->insertGetId([
                'name' => 'Ortu '.$key, 'phone' => '08'.random_int(100000000, 999999999), 'gender' => 'male',
                'balance' => 50000, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
            ]);
        }

        // sd: anak SD; smp: anak SMP; both: anak SD (ber-NIS "T-sd2") dan anak SMP
        $children = [
            'sd' => ['sd', 'sd'], 'smp' => ['smp', 'smp'],
            'both_sd' => ['sd', 'both'], 'both_smp' => ['smp', 'both'],
        ];

        foreach ($children as $key => [$level, $parentKey]) {
            $this->student[$key] = DB::table('student')->insertGetId([
                'id_parent' => $this->parent[$parentKey], 'id_class' => $this->class[$level],
                'nis' => 'T-'.$key, 'name' => 'Siswa '.$key, 'gender' => 'male', 'religion' => 'Islam',
                'exculs' => '[]', 'status' => 1, 'balance_savings' => 10000,
                'branch_id' => self::BRANCH_ID, 'created_by' => 0,
            ]);

            $this->bill[$key] = DB::table('transaction_bill')->insertGetId([
                'id_student' => $this->student[$key], 'id_bill' => $billId, 'months' => 7, 'years' => 2026,
                'total' => 100000, 'status' => 0, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
            ]);

            $this->withdrawal[$key] = DB::table('savings_withdrawal')->insertGetId([
                'id_student' => $this->student[$key], 'number' => 'W-'.$key, 'dates' => date('Y-m-d'),
                'total' => 5000, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
            ]);
        }

        // Transaksi lunas
        $this->tx['sd'] = $this->makeTransaction(1, ['id_student' => $this->student['sd'], 'id_parent' => $this->parent['sd']]);
        $this->tx['smp'] = $this->makeTransaction(1, ['id_student' => $this->student['smp'], 'id_parent' => $this->parent['smp']]);
        $this->tx['sd_unique'] = $this->makeTransaction(1, ['id_student' => $this->student['sd'], 'unique_code' => 123, 'total' => 100123]);
        $this->tx['smp_unique'] = $this->makeTransaction(1, ['id_student' => $this->student['smp'], 'unique_code' => 456, 'total' => 100456]);
        $this->tx['savings_sd'] = $this->makeTransaction(2, ['id_student' => $this->student['sd']]);
        $this->tx['savings_smp'] = $this->makeTransaction(2, ['id_student' => $this->student['smp']]);
        $this->tx['topup_sd'] = $this->makeTransaction(4, ['id_parent' => $this->parent['sd']]);
        $this->tx['topup_smp'] = $this->makeTransaction(4, ['id_parent' => $this->parent['smp']]);
        $this->tx['topup_both'] = $this->makeTransaction(4, ['id_parent' => $this->parent['both']]);
        // tagihan milik orang tua dengan anak di dua jenjang, tanpa siswa
        $this->tx['shared'] = $this->makeTransaction(1, ['id_parent' => $this->parent['both']]);
        // donasi: tanpa siswa dan tanpa orang tua
        $this->tx['donation'] = $this->makeTransaction(1, ['id_parent' => null, 'id_student' => null]);
    }

    private function makeTransaction(int $flag, array $attrs = []): int
    {
        static $n = 0;
        $n++;

        return DB::table('transaction')->insertGetId(array_merge([
            'number' => 'TX-'.$n.'-'.random_int(1000, 9999), 'dates' => date('Y-m-d'),
            'subtotal' => 100000, 'total' => 100000, 'flag' => $flag, 'payment_method' => 1,
            'status' => 1, 'paid_at' => date('Y-m-d H:i:s'), 'paid_by' => 0,
            'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ], $attrs));
    }

    /** Pegawai pemegang modul Kasir Unit dengan jenjang modul */
    private function kasirUnitModule(array $levels): User
    {
        $user = $this->createUser('pegawai', [Modules::KasirUnit]);

        foreach ($levels as $level) {
            DB::table('user_education_levels')->insert([
                'user_id' => $user->id, 'level_education' => $level, 'module_id' => Modules::KasirUnit,
            ]);
        }

        return User::withoutGlobalScopes()->find($user->id);
    }

    /** Akun kasir-unit lama dengan jenjang tanpa modul */
    private function kasirUnitLegacy(array $levels = []): User
    {
        return $this->createUser('kasir-unit', [], $levels);
    }

    private function dt(string $route, array $params = [], ?User $as = null)
    {
        return $this->actingAs($as)->post(route($route), array_merge([
            'draw' => 1, 'start' => 0, 'length' => 100, 'search' => ['value' => ''],
            'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d'),
        ], $params));
    }

    private function ids(array $keys): array
    {
        return collect($keys)->map(fn ($k) => $this->tx[$k])->sort()->values()->all();
    }

    private function dataIds($response): array
    {
        return collect($response->assertOk()->json('data'))->pluck('id')->sort()->values()->all();
    }

    // ------------------------------------------------------------------ aturan dasar

    public function test_class_ids_rules(): void
    {
        $sd = [$this->class['sd']];

        $this->assertSame($sd, $this->kasirUnitModule(['sd'])->kasirUnitClassIds());
        $this->assertSame($sd, $this->kasirUnitLegacy(['sd'])->kasirUnitClassIds());
        $this->assertNull($this->kasirUnitLegacy()->kasirUnitClassIds(), 'akun lama tanpa baris = tanpa batas');
        $this->assertNull($this->createUser('kasir')->kasirUnitClassIds());
        $this->assertNull($this->createUser('pegawai', [Modules::Kasir])->kasirUnitClassIds());
        $this->assertNull($this->createUser('bendahara')->kasirUnitClassIds());
        $this->assertNull($this->createUser('pegawai')->kasirUnitClassIds());

        // Kasir menang atas Kasir Unit
        $both = $this->createUser('pegawai', [Modules::Kasir, Modules::KasirUnit]);
        DB::table('user_education_levels')->insert(['user_id' => $both->id, 'level_education' => 'sd', 'module_id' => Modules::KasirUnit]);
        $this->assertNull(User::withoutGlobalScopes()->find($both->id)->kasirUnitClassIds());

        // modul tanpa jenjang menolak semua, bukan membuka semua
        $this->assertSame([], $this->kasirUnitModule([])->kasirUnitClassIds());
    }

    // ------------------------------------------------------------------ saldo (orang tua)

    public function test_parent_autocomplete_is_limited(): void
    {
        $user = $this->kasirUnitModule(['sd']);
        $names = fn ($u) => collect($this->actingAs($u)->get(route('finance.balance.get.autocomplete.parent', ['term' => 'Ortu']))
            ->assertOk()->json())->pluck('id')->sort()->values()->all();

        $this->assertSame(
            collect([$this->parent['sd'], $this->parent['both']])->sort()->values()->all(),
            $names($user)
        );
        $this->assertCount(3, $names($this->createUser('kasir')));
        $this->assertSame([], $names($this->kasirUnitModule([])));
    }

    public function test_get_parent_is_limited(): void
    {
        $phone = DB::table('parent')->where('id', $this->parent['smp'])->value('phone');
        $post = fn ($u) => $this->actingAs($u)->post(route('finance.balance.get.parent'), ['parent' => 'Ortu smp - '.$phone])
            ->assertOk()->json('data.id');

        $this->assertNull($post($this->kasirUnitModule(['sd'])));
        $this->assertSame($this->parent['smp'], $post($this->kasirUnitModule(['smp'])));
        $this->assertSame($this->parent['smp'], $post($this->createUser('kasir')));
    }

    public function test_balance_store_cash_is_limited(): void
    {
        $payload = fn ($p) => ['id_parent' => $this->parent[$p], 'nominal' => '10.000', 'dates' => date('Y-m-d')];
        $balance = fn ($p) => DB::table('parent')->where('id', $this->parent[$p])->value('balance');

        $sd = $this->kasirUnitModule(['sd']);
        $this->actingAs($sd)->post(route('finance.balance.store.cash'), $payload('smp'))->assertOk()->assertJson(['status' => false]);
        $this->assertEquals(50000, $balance('smp'));

        $this->actingAs($sd)->post(route('finance.balance.store.cash'), $payload('both'))->assertOk()->assertJson(['status' => true]);
        $this->assertEquals(60000, $balance('both'));
    }

    public function test_balance_store_withdrawal_is_limited(): void
    {
        $payload = fn ($p) => ['id_parent' => $this->parent[$p], 'nominal' => '10.000'];
        $balance = fn ($p) => DB::table('parent')->where('id', $this->parent[$p])->value('balance');

        $sd = $this->kasirUnitLegacy(['sd']);
        $this->actingAs($sd)->post(route('finance.balance.store.withdrawal'), $payload('smp'))->assertOk()->assertJson(['status' => false]);
        $this->assertEquals(50000, $balance('smp'));

        $this->actingAs($sd)->post(route('finance.balance.store.withdrawal'), $payload('sd'))->assertOk()->assertJson(['status' => true]);
        $this->assertEquals(40000, $balance('sd'));
    }

    // ------------------------------------------------------------------ tabungan (siswa)

    public function test_savings_get_student_is_limited(): void
    {
        $sd = $this->kasirUnitModule(['sd']);

        $this->actingAs($sd)->post(route('finance.savings.get.student'), ['nis' => 'T-smp'])->assertNotFound();
        $this->actingAs($sd)->post(route('finance.savings.get.student'), ['nis' => 'T-sd'])->assertOk();
        $this->actingAs($this->createUser('kasir'))->post(route('finance.savings.get.student'), ['nis' => 'T-smp'])->assertOk();
    }

    public function test_savings_deposit_history_is_limited(): void
    {
        $sd = $this->kasirUnitModule(['sd']);

        $this->assertSame(
            $this->ids(['savings_sd']),
            $this->dataIds($this->dt('finance.savings.datatable.history', [], $sd))
        );
        $this->assertSame(
            $this->ids(['savings_sd', 'savings_smp']),
            $this->dataIds($this->dt('finance.savings.datatable.history', [], $this->createUser('kasir')))
        );
    }

    public function test_savings_mutation_is_limited(): void
    {
        $sd = $this->kasirUnitModule(['sd']);
        $post = fn ($nis) => $this->actingAs($sd)->post(route('finance.savings.datatable.mutation'), [
            'student' => $nis.' - x', 'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => ''],
        ]);

        $post('T-smp')->assertNotFound();
        $post('T-sd')->assertOk();
    }

    public function test_savings_store_is_limited(): void
    {
        Event::fake([SavingsDepositPaid::class]);
        $sd = $this->kasirUnitModule(['sd']);

        $this->actingAs($sd)->post(route('finance.savings.store'), ['id_student' => $this->student['smp'], 'dates' => date('Y-m-d'), 'nominal' => '5.000'])
            ->assertForbidden();
        $this->assertDatabaseMissing('transaction', ['id_student' => $this->student['smp'], 'flag' => 2, 'total' => 5000]);

        $this->actingAs($sd)->post(route('finance.savings.store'), ['id_student' => $this->student['sd'], 'dates' => date('Y-m-d'), 'nominal' => '5.000'])
            ->assertOk()->assertJson(['status' => true]);
        $this->assertDatabaseHas('transaction', ['id_student' => $this->student['sd'], 'flag' => 2, 'total' => 5000]);
    }

    public function test_savings_get_withdrawal_is_limited(): void
    {
        $sd = $this->kasirUnitModule(['sd']);
        $response = $this->actingAs($sd)->post(route('finance.savings.get.withdrawal'), ['person' => 0])->assertOk();

        $this->assertEquals(
            [$this->withdrawal['sd'], $this->withdrawal['both_sd']],
            collect(array_keys($response->json('data.withdrawals')))->map(fn ($i) => (int) $i)->sort()->values()->all()
        );
    }

    public function test_savings_process_withdrawal_is_limited(): void
    {
        $sd = $this->kasirUnitModule(['sd']);
        $payload = fn ($keys) => [
            'id_parent' => 0,
            'dates' => date('Y-m-d'),
            'total' => 10000,
            'bills' => collect($keys)->mapWithKeys(fn ($k) => [$this->withdrawal[$k] => 5000])->all(),
        ];

        $this->actingAs($sd)->post(route('finance.savings.process.withdrawal'), $payload(['sd', 'smp']))->assertForbidden();
        $this->assertDatabaseMissing('transaction', ['flag' => 3, 'paid_by' => $sd->id]);
    }

    // ------------------------------------------------------------------ tagihan (siswa)

    public function test_get_bill_is_limited(): void
    {
        $sd = $this->kasirUnitModule(['sd']);
        $bills = fn ($nis, $u) => $this->actingAs($u)->post(route('finance.transaction.get.bill'), ['search' => $nis.' - Siswa '.substr($nis, 2)])
            ->assertOk()->json('data');

        $this->assertSame(0, $bills('T-smp', $sd)['student']);
        $this->assertEmpty($bills('T-smp', $sd)['bills']);
        $this->assertSame($this->student['sd'], $bills('T-sd', $sd)['student']);
        $this->assertSame($this->student['smp'], $bills('T-smp', $this->createUser('kasir'))['student']);
    }

    public function test_pay_bill_is_limited(): void
    {
        Event::fake([TransactionBillPaid::class]);
        $sd = $this->kasirUnitModule(['sd']);
        $payload = fn ($key) => [
            'id_student' => $this->student[$key], 'dates' => date('Y-m-d'), 'payment_method' => '1',
            'bills' => [$this->bill[$key] => 100000],
        ];

        $this->actingAs($sd)->post(route('finance.transaction.store'), $payload('smp'))->assertForbidden();
        $this->assertDatabaseHas('transaction_bill', ['id' => $this->bill['smp'], 'status' => 0]);

        // tagihan siswa lain tidak boleh ikut dibayar lewat siswa yang diizinkan
        $mixed = $payload('sd');
        $mixed['bills'][$this->bill['smp']] = 100000;
        $this->actingAs($sd)->post(route('finance.transaction.store'), $mixed)->assertForbidden();
        $this->assertDatabaseHas('transaction_bill', ['id' => $this->bill['smp'], 'status' => 0]);

        $this->actingAs($sd)->post(route('finance.transaction.store'), $payload('sd'))->assertOk()->assertJson(['status' => true]);
        $this->assertDatabaseHas('transaction_bill', ['id' => $this->bill['sd'], 'status' => 1]);
    }

    public function test_show_and_print_transaction_are_limited(): void
    {
        $sd = $this->kasirUnitModule(['sd']);
        $enc = fn ($key) => Transaction::withoutGlobalScopes()->find($this->tx[$key])->encrypted_id;

        $this->actingAs($sd)->get(route('finance.transaction.bill.show', $enc('smp')))->assertForbidden();
        $this->actingAs($sd)->get(route('finance.transaction.print', $enc('smp')))->assertForbidden();
        $this->actingAs($sd)->get(route('finance.transaction.bill.show', $enc('sd')))->assertOk();
        $this->actingAs($this->createUser('kasir'))->get(route('finance.transaction.bill.show', $enc('smp')))->assertOk();
    }

    public function test_update_status_is_limited(): void
    {
        $sd = $this->kasirUnitModule(['sd']);
        $enc = Transaction::withoutGlobalScopes()->find($this->tx['smp'])->encrypted_id;

        $this->actingAs($sd)->post(route('finance.transaction.update.status'), ['id' => $enc, 'status' => 'cancel'])->assertForbidden();
        $this->assertDatabaseHas('transaction', ['id' => $this->tx['smp'], 'deleted_at' => null]);
    }

    // ------------------------------------------------------------------ riwayat dan setoran kas

    public function test_transaction_history_is_limited(): void
    {
        $sd = $this->kasirUnitModule(['sd']);

        $this->assertSame(
            // tagihan sd, sd unique, setor tabungan sd, topup orang tua sd, topup orang tua sd+smp, tagihan orang tua sd+smp
            $this->ids(['sd', 'sd_unique', 'savings_sd', 'topup_sd', 'topup_both', 'shared']),
            $this->dataIds($this->dt('finance.transaction.datatable.history', [], $sd)),
            'donasi dan jenjang lain tidak tampil'
        );

        $this->assertSame(
            $this->ids(array_keys($this->tx)),
            $this->dataIds($this->dt('finance.transaction.datatable.history', [], $this->createUser('kasir'))),
            'kasir tidak dibatasi'
        );

        $this->assertSame([], $this->dataIds($this->dt('finance.transaction.datatable.history', [], $this->kasirUnitModule([]))));
        $this->assertCount(
            count($this->tx),
            $this->dataIds($this->dt('finance.transaction.datatable.history', [], $this->kasirUnitLegacy())),
            'akun lama tanpa jenjang tidak dibatasi'
        );
    }

    public function test_legacy_kasir_unit_with_levels_is_limited(): void
    {
        $this->assertSame(
            $this->ids(['sd', 'sd_unique', 'savings_sd', 'topup_sd', 'topup_both', 'shared']),
            $this->dataIds($this->dt('finance.transaction.datatable.history', [], $this->kasirUnitLegacy(['sd'])))
        );
    }

    public function test_deposit_candidates_are_limited(): void
    {
        $sd = $this->kasirUnitModule(['sd']);

        $this->assertSame(
            $this->ids(['sd', 'sd_unique', 'shared']),
            $this->dataIds($this->dt('finance.transaction.datatable.paid', [], $sd)),
            'donasi disembunyikan, jenjang lain tidak tampil'
        );
        $this->assertSame(
            $this->ids(['sd_unique']),
            $this->dataIds($this->dt('finance.transaction.datatable.paid-unique-code', [], $sd))
        );
        $this->assertSame(
            $this->ids(['sd', 'smp', 'sd_unique', 'smp_unique', 'shared', 'donation']),
            $this->dataIds($this->dt('finance.transaction.datatable.paid', [], $this->createUser('kasir')))
        );
    }

    public function test_cash_deposit_rejects_out_of_level_transactions(): void
    {
        $sd = $this->kasirUnitModule(['sd']);
        $post = fn ($keys) => $this->actingAs($sd)->post(route('finance.transaction.store.cash'), [
            'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d'), 'dates' => date('Y-m-d'),
            'transaction' => collect($keys)->map(fn ($k) => $this->tx[$k])->all(),
        ]);

        $post(['sd', 'smp'])->assertStatus(422)->assertJson(['status' => false]);
        $post(['donation'])->assertStatus(422);
        $post(['savings_sd'])->assertStatus(422);

        // tidak ada yang berubah
        $this->assertSame(0, DB::table('transaction')->whereIn('id', $this->ids(['sd', 'smp', 'donation']))->where('status_deposit', 1)->count());
        $this->assertSame(0, DB::table('cash_deposit')->where('created_by', $sd->id)->count());

        $post(['sd', 'shared'])->assertOk()->assertJson(['status' => true]);
        $this->assertSame(2, DB::table('transaction')->whereIn('id', $this->ids(['sd', 'shared']))->where('status_deposit', 1)->count());
    }

    public function test_two_kasir_unit_cannot_deposit_same_transaction(): void
    {
        $sd = $this->kasirUnitModule(['sd']);
        $smp = $this->kasirUnitModule(['smp']);
        $post = fn ($u, $keys) => $this->actingAs($u)->post(route('finance.transaction.store.cash'), [
            'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d'), 'dates' => date('Y-m-d'),
            'transaction' => collect($keys)->map(fn ($k) => $this->tx[$k])->all(),
        ]);

        // keduanya melihat transaksi orang tua dua jenjang
        $this->assertContains($this->tx['shared'], $this->dataIds($this->dt('finance.transaction.datatable.paid', [], $sd)));
        $this->assertContains($this->tx['shared'], $this->dataIds($this->dt('finance.transaction.datatable.paid', [], $smp)));

        $post($sd, ['shared'])->assertOk()->assertJson(['status' => true]);

        // Kasir Unit kedua menyetor transaksi yang sama (daftar lama di browser): seluruh proses ditolak
        $post($smp, ['smp', 'shared'])->assertStatus(422)->assertJson([
            'status' => false,
            'message' => __('message.deposit_already_deposited'),
        ]);

        $this->assertSame(1, DB::table('cash_deposit')->count());
        $this->assertDatabaseHas('transaction', ['id' => $this->tx['smp'], 'status_deposit' => 0]);
        $this->assertSame([], collect($this->dataIds($this->dt('finance.transaction.datatable.paid', [], $smp)))->intersect([$this->tx['shared']])->all());
    }

    public function test_cash_deposit_runs_in_transaction_and_locks_rows(): void
    {
        $sd = $this->kasirUnitModule(['sd']);
        $queries = [];
        DB::listen(function ($q) use (&$queries) {
            $queries[] = $q->sql;
        });

        $this->actingAs($sd)->post(route('finance.transaction.store.cash'), [
            'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d'), 'dates' => date('Y-m-d'),
            'transaction' => [$this->tx['sd']],
        ])->assertOk();

        $this->assertTrue(
            collect($queries)->contains(fn ($sql) => str_contains($sql, 'from `transaction`') && str_contains($sql, 'for update')),
            'baris transaksi dikunci dengan lockForUpdate'
        );
    }

    public function test_update_cash_deposit_rechecks_transactions(): void
    {
        $sd = $this->kasirUnitModule(['sd']);
        $base = ['start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d'), 'dates' => date('Y-m-d')];

        $this->actingAs($sd)->post(route('finance.transaction.store.cash'), $base + ['transaction' => [$this->tx['sd']]])->assertOk();
        $deposit = \App\Models\CashDeposit::withoutGlobalScopes()->where('created_by', $sd->id)->firstOrFail();

        // menambah transaksi jenjang lain ditolak, transaksi lama tetap tersetor
        $this->actingAs($sd)->post(route('finance.transaction.update.cash', $deposit->encrypted_id), $base + [
            'transaction' => [$this->tx['sd'], $this->tx['smp']],
        ])->assertStatus(422);
        $this->assertDatabaseHas('transaction', ['id' => $this->tx['sd'], 'status_deposit' => 1]);
        $this->assertDatabaseHas('transaction', ['id' => $this->tx['smp'], 'status_deposit' => 0]);

        // transaksi yang sudah ada di setoran ini boleh dikirim ulang, ditambah yang masih dalam jenjang
        $this->actingAs($sd)->post(route('finance.transaction.update.cash', $deposit->encrypted_id), $base + [
            'transaction' => [$this->tx['sd'], $this->tx['shared']],
        ])->assertOk()->assertJson(['status' => true]);
        $this->assertDatabaseHas('transaction', ['id' => $this->tx['shared'], 'status_deposit' => 1]);
    }

    public function test_unique_code_deposit_is_limited(): void
    {
        $sd = $this->kasirUnitModule(['sd']);
        $post = fn ($keys) => $this->actingAs($sd)->post(route('finance.transaction.store.unique-code'), [
            'dates' => date('Y-m-d'), 'transaction' => collect($keys)->map(fn ($k) => $this->tx[$k])->all(),
        ]);

        $post(['sd_unique', 'smp_unique'])->assertStatus(422)->assertJson(['status' => false]);
        $this->assertDatabaseHas('transaction', ['id' => $this->tx['sd_unique'], 'status_deposit_code' => 0]);
        $this->assertSame(0, DB::table('unique_code_deposit')->count());

        $post(['sd'])->assertStatus(422); // tanpa kode unik

        $post(['sd_unique'])->assertOk();
        $this->assertDatabaseHas('transaction', ['id' => $this->tx['sd_unique'], 'status_deposit_code' => 1]);

        // setor ulang transaksi yang sama ditolak
        $post(['sd_unique'])->assertStatus(422);
        $this->assertSame(1, DB::table('unique_code_deposit')->count());
    }

    public function test_update_unique_code_deposit_is_limited(): void
    {
        $sd = $this->kasirUnitModule(['sd']);
        $this->actingAs($sd)->post(route('finance.transaction.store.unique-code'), [
            'dates' => date('Y-m-d'), 'transaction' => [$this->tx['sd_unique']],
        ])->assertOk();
        $deposit = \App\Models\UniqueCodeDeposit::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($sd)->post(route('finance.transaction.update.unique-code', $deposit->encrypted_id), [
            'dates' => date('Y-m-d'), 'transaction' => [$this->tx['sd_unique'], $this->tx['smp_unique']],
        ])->assertStatus(422);
        $this->assertDatabaseHas('transaction', ['id' => $this->tx['smp_unique'], 'status_deposit_code' => 0]);
    }

    public function test_kasir_deposit_is_not_limited_but_still_rejects_double_deposit(): void
    {
        $kasir = $this->createUser('kasir');
        $post = fn ($keys) => $this->actingAs($kasir)->post(route('finance.transaction.store.cash'), [
            'start_date' => date('Y-m-d'), 'end_date' => date('Y-m-d'), 'dates' => date('Y-m-d'),
            'transaction' => collect($keys)->map(fn ($k) => $this->tx[$k])->all(),
        ]);

        $post(['sd', 'smp'])->assertOk()->assertJson(['status' => true]);
        $post(['sd'])->assertStatus(422);
    }

    // ------------------------------------------------------------------ dashboard dan autocomplete

    public function test_dashboard_count_is_limited(): void
    {
        $sd = $this->kasirUnitModule(['sd']);
        $get = fn ($u) => $this->actingAs($u)->post(route('dashboard.get.count'))->assertOk()->json('data');

        $limited = $get($sd);
        $this->assertEquals(20000, $limited['savings'], 'tabungan anak SD: T-sd dan T-both_sd');
        $this->assertEquals(300000, $limited['cash'], 'tagihan sd + sd_unique + shared, tanpa kode unik');
        $this->assertEquals(123, $limited['unique_code']);

        $all = $get($this->createUser('kasir'));
        $this->assertEquals(40000, $all['savings']);
        $this->assertEquals(150000, $all['topup']);
        $this->assertGreaterThan($limited['cash'], $all['cash']);
    }

    public function test_dashboard_progress_and_receipt_are_limited(): void
    {
        foreach (['sd', 'smp'] as $level) {
            DB::table('report_bill_class')->insert([
                'id_year' => $this->yearId, 'id_class' => $this->class[$level], 'dates' => date('Y-m-d'),
                'quantity' => 1, 'total' => 1000, 'branch_id' => self::BRANCH_ID,
            ]);
        }

        $sd = $this->kasirUnitModule(['sd']);

        $progress = $this->actingAs($sd)->post(route('dashboard.get.payment-progress'), ['year' => $this->yearId])->assertOk()->json('data.total');
        $this->assertEquals(100, $progress['bill']);

        $all = $this->actingAs($this->createUser('kasir'))->post(route('dashboard.get.payment-progress'), ['year' => $this->yearId])->json('data.total');
        $this->assertEquals(1000, $all['bill']);

        $this->assertSame(0, $this->actingAs($this->kasirUnitModule([]))
            ->post(route('dashboard.get.payment-progress'), ['year' => $this->yearId])->json('data.total.bill'));

        $this->actingAs($sd)->post(route('dashboard.get.receipt'), ['year' => $this->yearId])->assertOk()
            ->assertDontSee('Kelas SMP')->assertSee('Kelas SD');
    }

    public function test_student_autocomplete_is_limited(): void
    {
        $get = fn ($u) => collect($this->actingAs($u)->get(route('academic.student.get.autocomplete', ['term' => 'Siswa']))
            ->assertOk()->json())->pluck('id')->sort()->values()->all();

        $this->assertSame(
            collect([$this->student['sd'], $this->student['both_sd']])->sort()->values()->all(),
            $get($this->kasirUnitModule(['sd']))
        );
        $this->assertCount(4, $get($this->createUser('kasir')));
        $this->assertCount(4, $get($this->createUser('penanggung-jawab-tabungan')));
    }

    // ------------------------------------------------------------------ form edit user kasir-unit lama

    public function test_edit_form_sets_levels_for_legacy_kasir_unit(): void
    {
        $admin = $this->createUser('admin');
        $kasirUnit = $this->kasirUnitLegacy();
        $enc = $kasirUnit->encrypted_id;
        $base = [
            'name' => 'Kasir Unit X', 'phone' => '081200000001', 'email' => 'ku@example.test', 'gender' => 'male',
        ];

        $this->actingAs($admin)->get(route('user.edit', $enc))->assertOk()->assertSee('level_education[]', false);

        $this->actingAs($admin)->put(route('user.update', $enc), $base + ['level_education' => ['sd', 'smp']])->assertRedirect();
        $this->assertEqualsCanonicalizing(['sd', 'smp'], User::withoutGlobalScopes()->find($kasirUnit->id)->educationLevelValues());

        $this->actingAs($admin)->put(route('user.update', $enc), $base + ['level_education' => ['xx']])->assertSessionHasErrors('level_education.0');
        $this->assertEqualsCanonicalizing(['sd', 'smp'], User::withoutGlobalScopes()->find($kasirUnit->id)->educationLevelValues());

        // dikosongkan = tanpa batas
        $this->actingAs($admin)->put(route('user.update', $enc), $base)->assertRedirect();
        $fresh = User::withoutGlobalScopes()->find($kasirUnit->id);
        $this->assertSame([], $fresh->educationLevelValues());
        $this->assertNull($fresh->kasirUnitClassIds());
    }

    public function test_edit_form_does_not_show_level_for_other_roles(): void
    {
        $this->actingAs($this->createUser('admin'))
            ->get(route('user.edit', $this->createUser('bendahara')->encrypted_id))
            ->assertOk()->assertDontSee('level_education[]', false);
    }
}
