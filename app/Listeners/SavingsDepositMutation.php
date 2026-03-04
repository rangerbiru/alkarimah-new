<?php

namespace App\Listeners;

use App\Enums\SavingsMutationFlag;
use App\Events\SavingsDepositPaid;
use App\Models\SavingsMutation;
use App\Models\Student;
use App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SavingsDepositMutation
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
    public function handle(SavingsDepositPaid $event): void
    {
        $student = Student::select('id', 'balance_savings')->whereId($event->transaction->id_student)->first();
        $balance_before = $student->balance_savings;
        $student->balance_savings += $event->transaction->subtotal;
        $student->save();

        Transaction::whereId($event->transaction->id)->update([
            'bills' => ['before' => $balance_before, 'after' => $student->balance_savings]
        ]);

        SavingsMutation::create([
            'id_student' => $event->transaction->id_student,
            'id_transaction' => $event->transaction->id,
            'debit' => $event->transaction->subtotal,
            'credit' => 0,
            'balance' => $student->balance_savings,
            'flag' => SavingsMutationFlag::Deposit->value,
            'branch_id' => $event->transaction->branch_id,
            'created_at' => $event->transaction->paid_at,
            'created_by' => $event->transaction->paid_by,
        ]);
    }
}
