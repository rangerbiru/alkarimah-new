<?php

namespace App\Listeners;

use App\Events\TransactionBillPaid;
use App\Models\ReportStudent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class StudentReport
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
    public function handle(TransactionBillPaid $event): void
    {
        $report = ReportStudent::select('id', 'bill_paid', 'bill_not_paid')->whereIdStudent($event->transaction->id_student)->first();
        $report->bill_paid += $event->transaction->subtotal;
        $report->bill_not_paid -= $event->transaction->subtotal;
        $report->save();
    }
}
