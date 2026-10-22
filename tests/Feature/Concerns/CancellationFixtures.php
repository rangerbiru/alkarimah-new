<?php

namespace Tests\Feature\Concerns;

use App\Enums\TransactionFlag;
use App\Enums\TransactionMethod;
use App\Http\Controllers\Finance\TransactionController;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Data uji pembatalan transaksi. Laravel memanggil setUpCancellationFixtures() otomatis lewat
 * setUpTraits() (setUp + nama trait); jangan memanggilnya lagi dari setUp().
 *
 * Pembayaran dijalankan lewat kode yang sebenarnya (TransactionController::updateStatus, yang memicu
 * listener BillPaid / StudentReport / TopupUniqueCode / SavingsDepositMutation), bukan menulis tabelnya langsung.
 */
trait CancellationFixtures
{
    protected User $admin;

    protected User $kasir;

    protected User $ortu;

    protected int $parentId;

    protected int $studentId;

    protected int $classId;

    protected int $yearId;

    protected int $typeId;

    protected int $billId;

    /** @var array<int, int> */
    protected array $tbIds = [];

    protected function setUpCancellationFixtures(): void
    {
        $this->admin = $this->createUser('admin');
        $this->kasir = $this->createUser('kasir');
        $this->ortu = $this->createUser('orang-tua');

        $now = ['branch_id' => self::BRANCH_ID, 'created_by' => 0];

        $this->parentId = DB::table('parent')->insertGetId([
            'id_user' => $this->ortu->id, 'name' => 'Ortu Uji Batal', 'phone' => '0812-3456-7890',
            'gender' => 'male', 'balance' => 100000,
        ] + $now);
        $this->classId = DB::table('class')->insertGetId([
            'id_wali_kelas' => 0, 'name' => '7A', 'level_education' => 'smp', 'level_class' => 1,
        ] + $now);
        $this->studentId = DB::table('student')->insertGetId([
            'id_class' => $this->classId, 'id_parent' => $this->parentId, 'nis' => '2489', 'name' => 'Santri Uji Batal',
            'gender' => 'male', 'religion' => 'Islam',
        ] + $now);
        $this->yearId = DB::table('year')->insertGetId([
            'start_year' => 2026, 'start_month' => '07', 'end_year' => 2027, 'end_month' => '06',
        ] + $now);
        $this->typeId = DB::table('bill_type')->insertGetId(['name' => 'SPP Uji', 'period' => 2, 'spp' => 1] + $now);
        $this->billId = DB::table('bill')->insertGetId([
            'id_year' => $this->yearId, 'id_type' => $this->typeId, 'name' => 'SPP 2026', 'nominal' => 750000,
        ] + $now);

        foreach ([10, 11] as $month) {
            $this->tbIds[] = DB::table('transaction_bill')->insertGetId([
                'id_student' => $this->studentId, 'id_bill' => $this->billId, 'months' => $month, 'years' => 2026,
                'subtotal' => 750000, 'discount' => 0, 'total' => 750000, 'status' => 0, 'due_date' => '2026-'.$month.'-10',
            ] + $now);
        }

        DB::table('report_bill')->insert([
            'id_year' => $this->yearId, 'id_type' => $this->typeId, 'level' => 9,
            'total' => 1500000, 'paid' => 0, 'remaining' => 1500000, 'branch_id' => self::BRANCH_ID,
        ]);
        DB::table('report_student')->insert(['id_student' => $this->studentId, 'bill_paid' => 0, 'bill_not_paid' => 1500000]);
    }

    /** Diskon SPP 50.000 untuk bulan Oktober 2026 saja (periodik: Oktober belum terpakai). */
    protected function addDiscount(): int
    {
        return DB::table('bill_discount')->insertGetId([
            'id_student' => $this->studentId, 'id_bill' => $this->billId, 'id_year' => $this->yearId, 'nominal' => 50000,
            'applies_to' => json_encode(['2026-10' => 0]), 'status' => 1, 'branch_id' => self::BRANCH_ID, 'created_by' => 0,
        ]);
    }

    /** Transaksi pending buatan orang tua, lalu disetujui kasir. Mengembalikan id transaksi. */
    protected function createPending(array $attributes): int
    {
        $this->actingAs($this->ortu);

        return Transaction::create($attributes + ['dates' => date('Y-m-d')])->id;
    }

    protected function approve(int $id): void
    {
        $this->actingAs($this->kasir);

        $this->app->make(TransactionController::class)->updateStatus(Request::create('/', 'POST', [
            'id' => Crypt::encrypt($id), 'status' => 'paid', 'unique_code_status' => '1',
        ]));
    }

    /** TR transfer BSI untuk kedua tagihan, kode unik 484. Dengan diskon, nominal yang dikirim UI sudah dipotong diskon. */
    protected function payBillsByTransfer(bool $discount = false): int
    {
        $subtotal = $discount ? 1450000 : 1500000;

        $id = $this->createPending([
            'id_student' => $this->studentId, 'flag' => TransactionFlag::Tagihan->value, 'subtotal' => $subtotal,
            'total' => $subtotal + 484, 'unique_code' => 484, 'payment_method' => TransactionMethod::BSI->value,
            'bills' => $this->tbIds,
        ]);
        $this->approve($id);

        return $id;
    }

    /** ST 150.000 via BNI, kode unik 161. */
    protected function paySavingsDeposit(): int
    {
        $id = $this->createPending([
            'id_student' => $this->studentId, 'flag' => TransactionFlag::SetorTabungan->value, 'subtotal' => 150000,
            'total' => 150161, 'unique_code' => 161, 'payment_method' => TransactionMethod::BNI->value,
        ]);
        $this->approve($id);

        return $id;
    }

    /** TS 200.000 via BSI, kode unik 333. */
    protected function payTopup(): int
    {
        $id = $this->createPending([
            'id_parent' => $this->parentId, 'flag' => TransactionFlag::TopupSaldo->value, 'subtotal' => 200000,
            'total' => 200333, 'unique_code' => 333, 'payment_method' => TransactionMethod::BSI->value,
        ]);
        $this->approve($id);

        return $id;
    }

    /**
     * Ringkasan semua yang disentuh pembayaran. Tabel laporan harian (type / method / class) dijumlahkan karena
     * pembayaran membuat baris baru yang setelah dibalik bernilai 0. Setelah dibatalkan, ringkasan ini harus
     * sama persis dengan sebelum bayar.
     */
    protected function state(): array
    {
        $sums = fn (string $table) => DB::table($table)->where('id_year', $this->yearId)
            ->selectRaw('COALESCE(SUM(quantity), 0) AS quantity, COALESCE(SUM(total), 0) AS total')->first();

        return [
            'bills' => DB::table('transaction_bill')->where('id_student', $this->studentId)->orderBy('id')
                ->get(['id', 'status', 'id_transaction', 'subtotal', 'discount', 'discount_id', 'total'])->all(),
            'report_bill' => DB::table('report_bill')->where('id_year', $this->yearId)->orderBy('id')->get(['paid', 'remaining', 'total'])->all(),
            'report_bill_type' => $sums('report_bill_type'),
            'report_bill_method' => $sums('report_bill_method'),
            'report_bill_class' => $sums('report_bill_class'),
            'report_student' => DB::table('report_student')->where('id_student', $this->studentId)->first(['bill_paid', 'bill_not_paid']),
            'parent_balance' => (float) DB::table('parent')->where('id', $this->parentId)->value('balance'),
            'savings_balance' => (float) DB::table('student')->where('id', $this->studentId)->value('balance_savings'),
            'discounts' => DB::table('bill_discount')->where('id_student', $this->studentId)->orderBy('id')->get(['applies_to', 'status'])->all(),
        ];
    }

    /** Ambil <script> inline (tanpa src) dari HTML lalu jalankan `node --check`; lewati bila node tidak ada. */
    protected function assertInlineScriptsParse(string $html): void
    {
        if (trim((string) shell_exec('command -v node')) === '') {
            $this->markTestSkipped('node tidak tersedia');
        }

        preg_match_all('#<script(?![^>]*\bsrc=)[^>]*>(.*?)</script>#s', $html, $matches);

        foreach ($matches[1] as $index => $script) {
            if (trim($script) === '') {
                continue;
            }

            $file = tempnam(sys_get_temp_dir(), 'inline-script-').'.js';
            file_put_contents($file, $script);
            exec('node --check '.escapeshellarg($file).' 2>&1', $output, $code);
            unlink($file);

            $this->assertSame(0, $code, "Skrip inline #{$index} gagal di-parse:\n".implode("\n", $output));
        }
    }
}
