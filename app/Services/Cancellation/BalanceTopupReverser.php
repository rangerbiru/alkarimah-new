<?php

namespace App\Services\Cancellation;

/** TS: topup saldo via transfer. */
class BalanceTopupReverser extends BaseReverser
{
    public function plan(Context $ctx): void
    {
        $row = $ctx->topups->first();

        if ($ctx->topups->count() !== 1 || ! $this->same($row->debit, $ctx->transaction->subtotal) || (float) $row->credit != 0) {
            $ctx->block('ledger', 'cancel_ledger', ['detail' => 'riwayat saldo topup tidak ditemukan atau nominalnya tidak sesuai']);
        }

        if ($ctx->savings->isNotEmpty()) {
            $ctx->block('ledger', 'cancel_ledger', ['detail' => 'ada mutasi tabungan pada topup saldo']);
        }
    }
}
