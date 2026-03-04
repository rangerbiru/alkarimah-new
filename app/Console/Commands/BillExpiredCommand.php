<?php

namespace App\Console\Commands;

use App\Enums\TransactionMethod;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\TransactionPaymentCode;
use Illuminate\Console\Command;

class BillExpiredCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bill:expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update bill expired status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transaction = Transaction::select('id', 'unique_code')
            ->whereIn('payment_method', [TransactionMethod::BNI->value, TransactionMethod::BSI->value])
            ->where('expired_at', '<', date('Y-m-d H:i:s'))
            ->notPaid()
            ->get();

        foreach ($transaction as $t) {
            $t->status = TransactionStatus::Expired->value;
            $t->save();

            TransactionPaymentCode::whereCode($t->unique_code)->delete();
        }
    }
}
