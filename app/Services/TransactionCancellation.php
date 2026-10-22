<?php

namespace App\Services;

use App\Constants\Modules;
use App\Enums\SavingsMutationFlag;
use App\Enums\TransactionFlag;
use App\Enums\TransactionMethod;
use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Models\ModuleRights;
use App\Models\Parents;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Cancellation\Context;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Pembatalan transaksi lunas yang dibuat orang tua (transfer atau saldo). Semua nominal dikembalikan
 * dalam satu DB transaction lewat entri pembalik; baris lama tidak pernah diubah, kecuali
 * tagihan dan laporan yang memang dibalik. Kunci diambil berurutan: parent, transaction, student,
 * transaction_bill, baris laporan (sama dengan jalur bayar, supaya tidak saling menunggu).
 */
class TransactionCancellation
{
    public const LOG_NAME = 'transaction-cancellation';

    /**
     * Admin, atau pegawai pemegang modul Pembatalan Transaksi yang bukan kasir (modul Kasir / Kasir Unit):
     * pemisahan tugas, kasir yang menerima uang tidak membatalkan transaksinya sendiri.
     */
    public function allowed(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role !== UserRole::Pegawai) {
            return false;
        }

        $modules = ModuleRights::where('id_user', $user->id)->pluck('id_module')->map(fn ($id) => (int) $id)->all();

        return in_array(Modules::CancelTransaction, $modules, true)
            && array_intersect([Modules::Kasir, Modules::KasirUnit], $modules) === [];
    }

    /** Pratinjau: hanya membaca, tanpa kunci. */
    public function preview(int $id, ?User $actor = null): array
    {
        $actor = $this->authorize($actor);

        return $this->describe($this->inspect($id, $actor, false), $id);
    }

    /**
     * @return array{id: int, number: string, cancellation_id: int}
     *
     * @throws CancellationException
     */
    public function cancel(int $id, string $reason, ?User $actor = null): array
    {
        $actor = $this->authorize($actor);
        $reason = $this->validReason($reason);

        // Sebelum DB transaction: SELECT biasa di dalam transaction akan memaku snapshot sebelum kunci diambil
        $parent_id = $this->parentIdOf($id);

        return DB::transaction(function () use ($id, $reason, $actor, $parent_id) {
            $ctx = $this->inspect($id, $actor, true, $parent_id);

            if ($ctx->blocked()) {
                throw new CancellationException($ctx->blockers);
            }
            $this->step('checked');

            $ctx->reverser->apply($ctx, fn (string $name) => $this->step($name));

            $reversals = $this->reverseLedger($ctx);
            $this->step('ledger');

            $this->updateBalances($ctx);
            $this->step('balances');

            $cancellation_id = $this->markCancelled($ctx, $reason, $reversals);
            $this->step('status');

            return ['id' => (int) $ctx->transaction->id, 'number' => $ctx->transaction->number, 'cancellation_id' => $cancellation_id];
        });
    }

    /** Titik henti per tahap; kosong. Test menimpanya untuk menggagalkan proses di tengah jalan. */
    protected function step(string $name): void {}

    private function authorize(?User $actor): User
    {
        $actor ??= Auth::user();

        if (! $this->allowed($actor)) {
            throw new CancellationException([['code' => 'forbidden', 'message' => __('message.cancel_forbidden')]]);
        }

        return $actor;
    }

    private function validReason(string $reason): string
    {
        $reason = trim(preg_replace('/\s+/u', ' ', $reason));
        $words = $reason === '' ? 0 : count(explode(' ', $reason));
        $rule = config('transaction-cancellation.reason');

        if ($words < $rule['min_words'] || mb_strlen($reason) < $rule['min_chars']) {
            throw new CancellationException([[
                'code' => 'reason',
                'message' => __('message.cancel_reason', ['words' => $rule['min_words'], 'chars' => $rule['min_chars']]),
            ]]);
        }

        return $reason;
    }

    private function parentIdOf(int $id): ?int
    {
        $row = DB::table('transaction as t')
            ->leftJoin('student as s', 's.id', '=', 't.id_student')
            ->where('t.id', $id)
            ->selectRaw('COALESCE(t.id_parent, s.id_parent) AS parent_id')
            ->first();

        return $row && $row->parent_id ? (int) $row->parent_id : null;
    }

    private function inspect(int $id, User $actor, bool $lock, ?int $expected_parent = null): Context
    {
        $ctx = new Context($actor, $lock);

        // 1. parent lebih dulu (urutan kunci jalur bayar)
        if ($lock && $expected_parent) {
            $ctx->parent = $ctx->query('parent')->where('id', $expected_parent)->first();
        }

        // 2. transaksi
        $t = $ctx->query('transaction')->where('id', $id)->first();

        if ($t === null || $t->deleted_at !== null || ($actor->branch_id && (int) $t->branch_id !== (int) $actor->branch_id)) {
            $ctx->block('not_found', 'cancel_not_found');

            return $ctx;
        }

        $ctx->transaction = $t;

        if ((string) $t->status === TransactionStatus::Cancelled->value) {
            $done = DB::table('transaction_cancellation as c')
                ->leftJoin('users as u', 'u.id', '=', 'c.cancelled_by')
                ->where('c.id_transaction', $id)
                ->first(['c.cancelled_at', 'u.name']);

            $ctx->block('already_cancelled', 'cancel_already', [
                'date' => $done ? Carbon::parse($done->cancelled_at)->translatedFormat('d F Y H:i') : '-',
                'name' => $done->name ?? '-',
            ]);

            return $ctx;
        }

        if ((string) $t->status !== TransactionStatus::Paid->value) {
            $ctx->block('not_paid', 'cancel_not_paid');

            return $ctx;
        }

        $allowed_methods = config('transaction-cancellation.scope')[(string) $t->flag] ?? [];

        if (! in_array((string) $t->payment_method, $allowed_methods, true)) {
            $ctx->block('scope', 'cancel_scope');

            return $ctx;
        }

        $ctx->reverser = app(config('transaction-cancellation.reversers')[(string) $t->flag]);

        // 3. santri, lalu parent saat ini
        $ctx->student = $t->id_student ? $ctx->query('student')->where('id', $t->id_student)->first() : null;
        $parent_id = $t->id_parent ?: ($ctx->student->id_parent ?? null);

        if ($lock && (int) $parent_id !== (int) $expected_parent) {
            $ctx->block('changed', 'cancel_changed');

            return $ctx;
        }

        if ($parent_id && $ctx->parent === null) {
            $ctx->parent = $ctx->query('parent')->where('id', $parent_id)->first();
        }

        $this->checkOwnChild($ctx, $parent_id);
        $this->checkDeposits($ctx);
        $this->checkMonth($ctx);

        if ($ctx->reverser->dependsOnClass() && config('transaction-cancellation.rules.class_unchanged')) {
            $this->checkClassUnchanged($ctx);
        }

        // 4. riwayat saldo / tabungan yang ditulis pembayaran (asli, bukan pembalik)
        $ctx->topups = $ctx->query('topup_history')->where('id_transaction', $id)->whereNull('reversal_of')->orderBy('id')->get();
        $ctx->savings = $ctx->query('savings_mutation')->where('id_transaction', $id)->where('flag', 1)->whereNull('reversal_of')->orderBy('id')->get();

        $ctx->reverser->plan($ctx);
        $this->checkBalances($ctx);

        return $ctx;
    }

    /** Admin tidak boleh membatalkan transaksi anaknya sendiri (akun, lalu 10 digit terakhir nomor HP). */
    private function checkOwnChild(Context $ctx, $parent_id): void
    {
        if ($parent_id && in_array((int) $parent_id, CashierConflict::ownParentIds($ctx->actor), true)) {
            $ctx->block('own_child', 'cancel_own_child');
        }
    }

    private function checkDeposits(Context $ctx): void
    {
        $t = $ctx->transaction;

        if (! config('transaction-cancellation.rules.cash_deposit')) {
            return;
        }

        if ((int) $t->status_deposit === 1) {
            $deposit = $this->findDeposit('cash_deposit', $t->id);
            $ctx->block('cash_deposit', 'cancel_cash_deposit', [
                'number' => $deposit->number ?? '-',
                'date' => $deposit ? Carbon::parse($deposit->dates)->translatedFormat('d F Y') : '-',
            ]);
        }

        if ((int) $t->status_deposit_code === 1) {
            $deposit = $this->findDeposit('unique_code_deposit', $t->id);
            $ctx->block('code_deposit', 'cancel_code_deposit', [
                'number' => $deposit->number ?? '-',
                'date' => $deposit ? Carbon::parse($deposit->dates)->translatedFormat('d F Y') : '-',
            ]);
        }
    }

    /** `transactions` berisi daftar id (string) dalam JSON: persempit dengan LIKE, lalu cocokkan persis. */
    private function findDeposit(string $table, $transaction_id): ?object
    {
        return DB::table($table)
            ->where('transactions', 'like', '%'.$transaction_id.'%')
            ->orderBy('id')
            ->get()
            ->first(fn ($row) => in_array((string) $transaction_id, array_map('strval', (array) json_decode((string) $row->transactions, true)), true));
    }

    private function checkMonth(Context $ctx): void
    {
        $paid = Carbon::parse($ctx->transaction->paid_at);

        if ($paid->isSameMonth(now())) {
            return;
        }

        if (config('transaction-cancellation.rules.month_open')) {
            $ctx->block('month_closed', 'cancel_month_closed', [
                'month' => $paid->translatedFormat('F Y'),
                'current' => now()->translatedFormat('F Y'),
            ]);
        } else {
            $ctx->warn('month', 'cancel_month_warning', ['month' => $paid->translatedFormat('F Y')]);
        }
    }

    /**
     * Laporan per kelas dicatat dengan kelas saat bayar dan nilai itu tidak tersimpan, jadi santri yang
     * pindah kelas setelah dibayar membuat pembalikan laporan tidak bisa dipastikan benar.
     */
    private function checkClassUnchanged(Context $ctx): void
    {
        if ($ctx->student === null) {
            return;
        }

        $paid_at = $ctx->transaction->paid_at;
        $dates = [];

        $log = DB::table('activity_log')
            ->where('subject_type', Student::class)
            ->where('subject_id', $ctx->student->id)
            ->where('event', 'updated')
            ->where('created_at', '>', $paid_at)
            ->whereRaw("JSON_EXTRACT(properties, '$.attributes.id_class') IS NOT NULL")
            ->whereRaw("JSON_EXTRACT(properties, '$.attributes.id_class') <> JSON_EXTRACT(properties, '$.old.id_class')")
            ->min('created_at');

        $moved = DB::table('student_displacement_history')
            ->where('id_student', $ctx->student->id)
            ->where('created_at', '>', $paid_at)
            ->whereColumn('before_class_id', '<>', 'after_class_id')
            ->min('created_at');

        foreach ([$log, $moved] as $date) {
            if ($date !== null) {
                $dates[] = Carbon::parse($date);
            }
        }

        if ($dates !== []) {
            $ctx->block('class_changed', 'cancel_class_changed', ['date' => min($dates)->translatedFormat('d F Y')]);
        }
    }

    private function checkBalances(Context $ctx): void
    {
        if ($ctx->topups->contains(fn ($row) => ! $ctx->parent || (int) $row->id_parent !== (int) $ctx->parent->id)) {
            $ctx->block('ledger', 'cancel_ledger', ['detail' => 'riwayat saldo milik orang tua lain atau orang tua tidak ditemukan']);
        }

        if ($ctx->savings->contains(fn ($row) => ! $ctx->student || (int) $row->id_student !== (int) $ctx->student->id)) {
            $ctx->block('ledger', 'cancel_ledger', ['detail' => 'mutasi tabungan tanpa santri atau milik santri lain']);
        }

        $ctx->parent_delta = (float) ($ctx->topups->sum('credit') - $ctx->topups->sum('debit'));
        $ctx->student_delta = (float) ($ctx->savings->sum('credit') - $ctx->savings->sum('debit'));

        if ($ctx->parent && $ctx->parent_delta < 0 && (float) $ctx->parent->balance + $ctx->parent_delta < -0.005) {
            $ctx->block('balance_parent', 'cancel_balance_parent', [
                'balance' => $this->money($ctx->parent->balance),
                'amount' => $this->money(-$ctx->parent_delta),
            ]);
        }

        if ($ctx->student && $ctx->student_delta < 0 && (float) $ctx->student->balance_savings + $ctx->student_delta < -0.005) {
            $ctx->block('balance_student', 'cancel_balance_student', [
                'balance' => $this->money($ctx->student->balance_savings),
                'amount' => $this->money(-$ctx->student_delta),
            ]);
        }
    }

    /**
     * Tiap baris asli mendapat satu pembalik dengan debit dan kredit ditukar.
     *
     * @return array{topups: array<int, int>, savings: array<int, int>} id asli => id pembalik
     */
    private function reverseLedger(Context $ctx): array
    {
        $t = $ctx->transaction;
        $actor = $ctx->actor->id;
        $map = ['topups' => [], 'savings' => []];

        $running = (float) ($ctx->parent->balance ?? 0);
        foreach ($ctx->topups as $row) {
            $running = round($running + (float) $row->credit - (float) $row->debit, 2);
            $map['topups'][$row->id] = DB::table('topup_history')->insertGetId([
                'id_parent' => $row->id_parent,
                'id_transaction' => $t->id,
                'reversal_of' => $row->id,
                'description' => __('string.cancellation_ledger', ['number' => $t->number]),
                'debit' => $row->credit,
                'credit' => $row->debit,
                'balance' => $running,
                'created_at' => now(),
                'created_by' => $actor,
            ]);
        }

        $running = (float) ($ctx->student->balance_savings ?? 0);
        foreach ($ctx->savings as $row) {
            $running = round($running + (float) $row->credit - (float) $row->debit, 2);
            $map['savings'][$row->id] = DB::table('savings_mutation')->insertGetId([
                'id_student' => $row->id_student,
                'id_transaction' => $t->id,
                'reversal_of' => $row->id,
                'debit' => $row->credit,
                'credit' => $row->debit,
                'balance' => $running,
                'flag' => SavingsMutationFlag::Cancellation->value,
                'branch_id' => $row->branch_id,
                'created_at' => now(),
                'created_by' => $actor,
            ]);
        }

        return $map;
    }

    /** Lewat Eloquent supaya perubahan saldo tercatat di activity log. */
    private function updateBalances(Context $ctx): void
    {
        if ($ctx->parent && $ctx->topups->isNotEmpty()) {
            $parent = Parents::withoutGlobalScopes()->findOrFail($ctx->parent->id);
            $before = (float) $parent->balance;
            $parent->balance = round($before + $ctx->parent_delta, 2);
            $parent->save();
            $ctx->balances['parent'] = ['before' => $before, 'after' => (float) $parent->balance];
        }

        if ($ctx->student && $ctx->savings->isNotEmpty()) {
            $student = Student::withoutGlobalScopes()->findOrFail($ctx->student->id);
            $before = (float) $student->balance_savings;
            $student->balance_savings = round($before + $ctx->student_delta, 2);
            $student->save();
            $ctx->balances['student'] = ['before' => $before, 'after' => (float) $student->balance_savings];
        }
    }

    private function markCancelled(Context $ctx, string $reason, array $reversals): int
    {
        $t = $ctx->transaction;

        $transaction = Transaction::withoutGlobalScopes()->withTrashed()->findOrFail($t->id);
        $transaction->status = TransactionStatus::Cancelled;
        $transaction->save();

        $snapshot = [
            'transaction' => [
                'number' => $t->number,
                'flag' => $t->flag,
                'payment_method' => $t->payment_method,
                'paid_at' => $t->paid_at,
                'paid_by' => $t->paid_by,
                'subtotal' => $t->subtotal,
                'discount' => $t->discount,
                'unique_code' => $t->unique_code,
                'total' => $t->total,
                'id_student' => $t->id_student,
                'id_parent' => $t->id_parent,
            ],
            'ledger' => [
                'topups' => $ctx->topups->map(fn ($r) => ['id' => $r->id, 'debit' => $r->debit, 'credit' => $r->credit, 'reversal_id' => $reversals['topups'][$r->id] ?? null])->all(),
                'savings' => $ctx->savings->map(fn ($r) => ['id' => $r->id, 'debit' => $r->debit, 'credit' => $r->credit, 'reversal_id' => $reversals['savings'][$r->id] ?? null])->all(),
            ],
            'balances' => $ctx->balances,
            'plan' => $ctx->plan,
            'warnings' => array_column($ctx->warnings, 'code'),
        ];

        $cancellation_id = DB::table('transaction_cancellation')->insertGetId([
            'id_transaction' => $t->id,
            'reason' => $reason,
            'cancelled_by' => $ctx->actor->id,
            'cancelled_at' => now(),
            'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'branch_id' => $t->branch_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        activity(self::LOG_NAME)
            ->performedOn($transaction)
            ->causedBy($ctx->actor)
            ->event('cancelled')
            ->withProperties(['cancellation_id' => $cancellation_id, 'reason' => $reason])
            ->log('Transaksi #'.$t->number.' dibatalkan');

        return $cancellation_id;
    }

    private function describe(Context $ctx, int $id): array
    {
        $t = $ctx->transaction;
        $parent = $ctx->parent;
        $student = $ctx->student;

        $summary = fn ($before, $delta) => ['before' => (float) $before, 'after' => round((float) $before + $delta, 2), 'delta' => $delta];

        return [
            'can_cancel' => ! $ctx->blocked(),
            'blockers' => $ctx->blockers,
            'warnings' => $ctx->warnings,
            'transaction' => $t === null ? ['id' => $id] : [
                'id' => (int) $t->id,
                'number' => $t->number,
                'flag' => (string) $t->flag,
                'payment_method' => (string) $t->payment_method,
                'paid_at' => $t->paid_at,
                'subtotal' => (float) $t->subtotal,
                'discount' => (float) $t->discount,
                'unique_code' => (float) $t->unique_code,
                'total' => (float) $t->total,
            ],
            'student' => $student ? ['id' => (int) $student->id, 'nis' => $student->nis, 'name' => $student->name] : null,
            'parent' => $parent ? ['id' => (int) $parent->id, 'name' => $parent->name, 'phone' => $parent->phone] : null,
            'parent_balance' => $parent && $ctx->topups->isNotEmpty() ? $summary($parent->balance, $ctx->parent_delta) : null,
            'student_savings' => $student && $ctx->savings->isNotEmpty() ? $summary($student->balance_savings, $ctx->student_delta) : null,
            'unique_code_reversed' => $t && (string) $t->flag !== TransactionFlag::TopupSaldo->value ? (float) $ctx->topups->sum('debit') : 0.0,
            'refund' => $t === null ? null : [
                'mode' => (string) $t->payment_method === TransactionMethod::TopupBalance->value ? 'balance' : 'transfer',
                'amount' => (float) $t->total,
            ],
            'details' => $ctx->reverser ? $ctx->reverser->describe($ctx) : [],
        ];
    }

    private function money($amount): string
    {
        return number_format((float) $amount, 0, ',', '.');
    }
}
