<?php

namespace App\Console\Commands;

use App\Models\TransactionPaymentCode;
use Illuminate\Console\Command;

class PaymentCodeExpiredCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:payment-code-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete payment code that has been expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        TransactionPaymentCode::where('expired_at', '<', date('Y-m-d H:i:s'))->notUsed()->delete();
    }
}
