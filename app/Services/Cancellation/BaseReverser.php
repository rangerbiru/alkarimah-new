<?php

namespace App\Services\Cancellation;

abstract class BaseReverser implements TransactionReverser
{
    public function apply(Context $ctx, callable $step): void {}

    public function describe(Context $ctx): array
    {
        return [];
    }

    public function dependsOnClass(): bool
    {
        return false;
    }

    protected function same($a, $b): bool
    {
        return abs((float) $a - (float) $b) < 0.5;
    }

    /**
     * Kode unik 0 berarti tidak boleh ada baris topup; kode unik > 0 berarti tepat satu baris
     * dengan debit = kode unik dan kredit 0 (ditulis listener TopupUniqueCode).
     *
     * @param  iterable<object>  $rows
     */
    protected function checkUniqueCodeRow(Context $ctx, iterable $rows): void
    {
        $rows = collect($rows);
        $code = (float) $ctx->transaction->unique_code;

        if ($code == 0) {
            if ($rows->isNotEmpty()) {
                $ctx->block('ledger', 'cancel_ledger', ['detail' => 'ada riwayat saldo padahal tidak ada kode unik']);
            }

            return;
        }

        $row = $rows->first();

        if ($rows->count() !== 1 || ! $this->same($row->debit, $code) || (float) $row->credit != 0) {
            $ctx->block('ledger', 'cancel_ledger', ['detail' => 'baris kode unik di riwayat saldo tidak ditemukan atau tidak sesuai']);
        }
    }
}
