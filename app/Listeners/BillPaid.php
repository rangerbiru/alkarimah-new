<?php

namespace App\Listeners;

use App\Events\TransactionBillPaid;
use App\Models\BillDiscount;
use App\Models\ReportBillClass;
use App\Models\ReportBillMethod;
use App\Models\Transaction;
use App\Models\TransactionBill;
use Illuminate\Support\Str;

class BillPaid
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
        $date = date('Y-m-d', strtotime($event->transaction->paid_at));
        $discount_total = 0;

        foreach ($event->transaction->bills as $b) {
            $transbill = TransactionBill::select('id', 'id_bill', 'id_student', 'months', 'years', 'semester', 'subtotal')
                ->with([
                    'bill' => function ($query) {
                        $query->select('id', 'id_year', 'id_type')
                            ->with(['type' => fn($qt) => $qt->select('id', 'period')]);
                    }
                ])
                ->whereId($b)
                ->first();

            $bill_discount = BillDiscount::select('id', 'id_bill', 'applies_to', 'nominal')
                ->whereIdStudent($transbill->id_student)
                ->whereIdBill($transbill->bill->id)
                ->first();
            
            $transbill_total = $transbill->subtotal;

            if (!empty($bill_discount)) {
                $discount = 0;
                $discount_id = $bill_discount->id;

                if (empty($bill_discount->applies_to))
                    $discount = $bill_discount->nominal;
                else {
                    $applies = json_decode(json_encode($bill_discount->applies_to), true);

                    if ($transbill->bill->type->is_period_monthly) {
                        $month = $transbill->years . '-' . Str::padLeft($transbill->months, 2, '0');

                        if (array_key_exists($month, $applies)) {
                            $applies[$month] = 1;
                            $discount = $bill_discount->nominal;
                        }
                    } else {
                        if (array_key_exists($transbill->semester, $applies)) {
                            $applies[$transbill->semester] = 1;
                            $discount = $bill_discount->nominal;
                        }
                    }

                    if ($discount > 0) {
                        $discount_status = false;

                        foreach ($applies as $a) {
                            if ($a == 0)
                                $discount_status = true;
                        }

                        $bill_discount->update([
                            'applies_to' => $applies,
                            'status' => $discount_status
                        ]);
                    }
                }

                $discount_total += $discount;

                $transbill_total = $transbill->subtotal - $discount;
                $transbill->update([
                    'discount' => $discount,
                    'discount_id' => $discount_id,
                    'total' => $transbill_total
                ]);
            }

            $report = ReportBillMethod::select('id', 'quantity', 'total')
                ->whereIdYear($transbill->bill->id_year)
                ->whereDates($date)
                ->whereMethod($event->transaction->payment_method->value)
                ->first();

            if (empty($report)) {
                ReportBillMethod::create([
                    'id_year' => $transbill->bill->id_year,
                    'dates' => $date,
                    'method' => $event->transaction->payment_method->value,
                    'quantity' => 1,
                    'total' => $transbill_total,
                    'branch_id' => $event->transaction->branch_id,
                ]);
            } else {
                $report->quantity += 1;
                $report->total += $transbill_total;
                $report->save();
            }

            $report = ReportBillClass::select('id', 'quantity', 'total')
                ->whereIdYear($transbill->bill->id_year)
                ->whereDates($date)
                ->whereIdClass($event->transaction->student->class->id)
                ->first();

            if (empty($report)) {
                ReportBillClass::create([
                    'id_year' => $transbill->bill->id_year,
                    'dates' => $date,
                    'id_class' => $event->transaction->student->class->id,
                    'quantity' => 1,
                    'total' => $transbill_total,
                    'branch_id' => $event->transaction->branch_id,
                ]);
            } else {
                $report->quantity += 1;
                $report->total += $transbill_total;
                $report->save();
            }
        }

        if ($discount_total > 0) {
            Transaction::whereId($event->transaction->id)->update([
                'subtotal' => $event->transaction->subtotal + $discount_total,
                'discount' => $discount_total
            ]);
        }
    }
}
