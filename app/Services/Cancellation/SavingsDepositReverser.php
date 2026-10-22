<?php

namespace App\Services\Cancellation;

/** ST: setor tabungan via transfer. */
class SavingsDepositReverser extends BaseReverser
{
    public function plan(Context $ctx): void
    {
        $t = $ctx->transaction;
        $row = $ctx->savings->first();

        if (
            $ctx->savings->count() !== 1
            || ! $this->same($row->debit, $t->subtotal)
            || (float) $row->credit != 0
            || (int) $row->id_student !== (int) $t->id_student
        ) {
            $ctx->block('ledger', 'cancel_ledger', ['detail' => 'mutasi setoran tabungan tidak ditemukan atau tidak sesuai']);
        }

        $this->checkUniqueCodeRow($ctx, $ctx->topups);
    }
}
