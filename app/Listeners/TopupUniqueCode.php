<?php

namespace App\Listeners;

use App\Events\SavingsDepositPaid;
use App\Events\TransactionBillPaid;
use App\Models\Parents;
use App\Models\TopupHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class TopupUniqueCode
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TransactionBillPaid|SavingsDepositPaid $event): void
    {
        if ($event->transaction->unique_code > 0) {
            $parent = Parents::select('id', 'balance')->whereId($event->transaction->student->id_parent)->first();
            $parent->balance += $event->transaction->unique_code;
            $parent->save();

            TopupHistory::create([
                'id_parent' => $parent->id,
                'id_transaction' => $event->transaction->id,
                'description' => 'Kode Unik dari Transaksi #' . $event->transaction->number,
                'debit' => $event->transaction->unique_code,
                'balance' => $parent->balance
            ]);
        }
    }
}
