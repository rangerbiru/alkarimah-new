<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aksi kasir ditolak karena konflik kepentingan: pelaku adalah orang tua dari santri yang diproses.
 *
 * Dilempar dari dalam DB::transaction bila perlu; penolakan dicatat ke activity_log di render(),
 * yaitu SETELAH transaksi dibatalkan, supaya catatannya tidak ikut ter-rollback.
 */
class CashierConflictException extends RuntimeException
{
    /**
     * @param  array<int, int>  $studentIds
     * @param  array<int, int>  $parentIds
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $action,
        public readonly array $studentIds = [],
        public readonly array $parentIds = [],
    ) {
        parent::__construct(__('message.cashier_conflict'));
    }

    public function report(): bool
    {
        return false;
    }

    public function render(Request $request): Response
    {
        try {
            activity('cashier-conflict')
                ->causedBy(\App\Models\User::withoutGlobalScopes()->find($this->userId))
                ->withProperties([
                    'action' => $this->action,
                    'student_ids' => $this->studentIds,
                    'parent_ids' => $this->parentIds,
                    'route' => $request->route()?->getName(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                ])
                ->log('Cashier conflict of interest denied');
        } catch (\Throwable $e) {
            Log::error('cashier-conflict-log-failed', ['error' => $e->getMessage(), 'user_id' => $this->userId, 'action' => $this->action]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => false, 'message' => $this->getMessage()], 403);
        }

        return response($this->getMessage(), 403);
    }
}
