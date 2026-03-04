<?php

namespace App\Listeners;

use App\Enums\SavingsMutationFlag;
use App\Enums\SavingsWithdrawalStatus;
use App\Events\SavingsWithdrawalProcessed;
use App\Models\SavingsMutation;
use App\Models\SavingsWithdrawal;
use App\Models\Student;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SavingsWithdrawalMutation
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
    public function handle(SavingsWithdrawalProcessed $event): void
    {
        $withdrawal = SavingsWithdrawal::whereId($event->withdrawal)->first();
        $withdrawal->update([
            'status' => SavingsWithdrawalStatus::Processed->value,
            'processed_at' => $event->transaction->paid_at,
            'processed_by' => $event->transaction->paid_by,
        ]);

        $student = Student::select('balance_savings')->whereId($withdrawal->id_student)->first();

        SavingsMutation::create([
            'id_student' => $withdrawal->id_student,
            'id_transaction' => $withdrawal->id,
            'debit' => 0,
            'credit' => $withdrawal->total,
            'balance' => $student->balance_savings,
            'flag' => SavingsMutationFlag::Withdrawal->value,
            'branch_id' => $withdrawal->branch_id,
            'created_at' => $event->transaction->paid_at,
            'created_by' => $event->transaction->paid_by,
        ]);
    }
}
