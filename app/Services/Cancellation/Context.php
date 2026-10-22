<?php

namespace App\Services\Cancellation;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Keadaan satu pemeriksaan pembatalan. Baris dibaca lewat query builder (bukan Eloquent) supaya
 * tidak terkena global scope dan bisa dikunci (lockForUpdate) pada pembatalan yang sebenarnya.
 */
class Context
{
    public ?object $transaction = null;

    public ?object $parent = null;

    public ?object $student = null;

    public ?TransactionReverser $reverser = null;

    /** @var Collection<int, object> baris topup_history asli (bukan pembalik) milik transaksi */
    public Collection $topups;

    /** @var Collection<int, object> baris savings_mutation setoran asli milik transaksi */
    public Collection $savings;

    public float $parent_delta = 0;

    public float $student_delta = 0;

    /** @var array<string, mixed> */
    public array $plan = [];

    /** @var array<string, array{before: float, after: float}> */
    public array $balances = [];

    /** @var array<int, array{code: string, message: string}> */
    public array $blockers = [];

    /** @var array<int, array{code: string, message: string}> */
    public array $warnings = [];

    public function __construct(public readonly User $actor, public readonly bool $lock)
    {
        $this->topups = collect();
        $this->savings = collect();
    }

    public function query(string $table): Builder
    {
        $query = DB::table($table);

        return $this->lock ? $query->lockForUpdate() : $query;
    }

    public function block(string $code, string $key, array $replace = []): void
    {
        $this->blockers[] = ['code' => $code, 'message' => __('message.'.$key, $replace)];
    }

    public function warn(string $code, string $key, array $replace = []): void
    {
        $this->warnings[] = ['code' => $code, 'message' => __('message.'.$key, $replace)];
    }

    public function blocked(): bool
    {
        return $this->blockers !== [];
    }
}
