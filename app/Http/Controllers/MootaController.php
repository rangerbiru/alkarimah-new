<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Events\SavingsDepositPaid;
use App\Events\TransactionBillPaid;
use App\Models\MootaLog;
use App\Models\Parents;
use App\Models\Student;
use App\Models\TopupHistory;
use App\Models\Transaction;
use App\Models\TransactionBill;
use App\Models\TransactionPaymentCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MootaController extends Controller
{
    public function notification(Request $request)
    {
        $bank = $request->bank;
        $user_id = Config::get('ref.moota.user');
        $bank_id = Config::get('ref.moota.credential.' . $bank . '.id');
        $secret = Config::get('ref.moota.credential.' . $bank . '.secret');
        $header = (object) [
            'user' => $request->server('HTTP_X_MOOTA_USER') ?? '',
            'bank' => $request->server('HTTP_X_MOOTA_WEBHOOK') ?? '',
            'signature' => $request->server('HTTP_SIGNATURE') ?? '',
        ];
        $data = $request->getContent();

        if ($header->user != $user_id) {
            Log::channel('payment')->alert('-----');
            Log::channel('payment')->alert('Moota/notification Failed');
            Log::channel('payment')->alert('Headers : ' . json_encode($header));
            Log::channel('payment')->alert('Data : ' . $data);
            Log::channel('payment')->alert('Response : Unauthorized Moota User');

            return response()->json([
                'status' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Unauthorized Moota User'
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($header->bank != $bank_id) {
            Log::channel('payment')->alert('-----');
            Log::channel('payment')->alert('Moota/notification Failed');
            Log::channel('payment')->alert('Headers : ' . json_encode($header));
            Log::channel('payment')->alert('Data : ' . $data);
            Log::channel('payment')->alert('Response : Unauthorized Moota Webhook');

            return response()->json([
                'status' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Unauthorized Moota Webhook'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $signature = hash_hmac('sha256', $data, $secret);

        if (!hash_equals($header->signature, $signature)) {
            Log::channel('payment')->alert('-----');
            Log::channel('payment')->alert('Moota/notification Failed');
            Log::channel('payment')->alert('Headers : ' . json_encode($header));
            Log::channel('payment')->alert('Data : ' . $data);
            Log::channel('payment')->alert('Signature (Al-Karimah) : ' . $signature);
            Log::channel('payment')->alert('Response : Invalid Signature');

            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Invalid Signature'
            ], Response::HTTP_BAD_REQUEST);
        }

        $moota = json_decode($data);

        foreach ($moota as $m) {
            $transaction = Transaction::select('id', 'id_parent', 'id_student', 'number', 'bills', 'subtotal', 'unique_code', 'branch_id', 'flag')
                ->whereTotal($m->amount)
                ->notPaid();

            if ($bank == 'bsi')
                $transaction = $transaction->bsi()->first();
            else
                $transaction = $transaction->bni()->first();

            if (empty($transaction))
                continue;

            DB::transaction(function () use ($transaction, $m) {
                MootaLog::create([
                    'id_transaction' => $transaction->id,
                    'id_branch' => $transaction->branch_id,
                    'data' => json_encode($m)
                ]);

                $transaction->update([
                    'status' => TransactionStatus::Paid->value,
                    'paid_at' => date('Y-m-d H:i:s'),
                    'paid_by' => 0,
                ]);

                if ($transaction->unique_code > 0)
                    TransactionPaymentCode::whereCode($transaction->unique_code)->delete();

                if ($transaction->is_tagihan) {
                    foreach ($transaction->bills as $b) {
                        $trans_bill =  TransactionBill::select('id', 'id_bill', 'id_student', 'total', 'status', 'branch_id')
                            ->with([
                                'bill' => fn($query) => $query->select('id', 'id_year', 'id_type'),
                                'student' => function ($query) {
                                    $query->select('id', 'id_class')
                                        ->with(['class' => fn($qc) => $qc->select('id', 'level_education', 'level_class')]);
                                }
                            ])
                            ->whereId($b)
                            ->first();

                        $trans_bill->update([
                            'id_transaction' => $transaction->id,
                            'status' => TransactionStatus::Paid->value
                        ]);

                        TransactionBill::updateReport($trans_bill, $transaction->paid_at);
                    }

                    $transaction = Transaction::whereId($transaction->id)->first();
                    event(new TransactionBillPaid($transaction));
                } else if ($transaction->is_setor_tabungan) {
                    $transaction = Transaction::whereId($transaction->id)->first();
                    event(new SavingsDepositPaid($transaction));
                } else { // Topup Saldo
                    $parent = Parents::select('id', 'balance')->whereId($transaction->id_parent)->first();
                    $parent->balance += $transaction->subtotal;
                    $parent->save();

                    TopupHistory::create([
                        'id_parent' => $parent->id,
                        'id_transaction' => $transaction->id,
                        'description' => 'Topup Saldo',
                        'debit' => $transaction->subtotal,
                        'balance' => $parent->balance
                    ]);
                }
            });
        }

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'Payment Successfully'
        ], Response::HTTP_OK);
    }
}
