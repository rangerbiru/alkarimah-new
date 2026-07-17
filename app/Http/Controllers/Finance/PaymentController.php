<?php

namespace App\Http\Controllers\Finance;

use App\Enums\BillPeriod;
use App\Enums\PaymentCodeStatus;
use App\Enums\TransactionFlag;
use App\Enums\TransactionMethod;
use App\Enums\TransactionStatus;
use App\Events\TransactionBillPaid;
use App\Http\Controllers\Controller;
use App\Models\BillDiscount;
use App\Models\Parents;
use App\Models\ReportStudent;
use App\Models\Student;
use App\Models\TopupHistory;
use App\Models\Transaction;
use App\Models\TransactionBill;
use App\Models\TransactionPaymentCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    private $title = 'label.payment';

    private $icon = 'ti ti-credit-card-filled';

    private $path = 'backend.finance.payment.';

    public function index()
    {
        $user = Auth::user();
        $method = (object) [
            'balance' => TransactionMethod::TopupBalance->value,
            'bni' => TransactionMethod::BNI->value,
            'bsi' => TransactionMethod::BSI->value,
        ];

        $students = Student::select('id', 'name')->whereIdParent($user->parent->id)->orderBy('name')->pluck('name', 'id');
        $student_count = count($students);
        $st = Student::select('id')->whereIdParent($user->parent->id)->orderBy('name')->limit(1)->first();
        $student_first = $st->id;
        $payment_code = TransactionPaymentCode::generate(TransactionFlag::Tagihan->value);

        return view($this->path.'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'path' => $this->path,
            'user' => $user,
            'method' => $method,
            'students' => $students,
            'student_count' => $student_count,
            'student_first' => $student_first,
            'payment_code' => $payment_code,
        ]);
    }

    public function waiting(Transaction $transaction)
    {
        if ($transaction->is_paid) {
            return Redirect::route('finance.payment.show', $transaction->encrypted_id);
        }

        return view($this->path.'waiting', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'transaction' => $transaction,
            'bills_detail' => $transaction->bills_detail,
        ]);
    }

    public function history()
    {
        $status_paid = TransactionStatus::Paid->value;

        return view($this->path.'history', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'path' => $this->path,
            'status_paid' => $status_paid,
        ]);
    }

    public function show(Transaction $transaction)
    {
        return view($this->path.'show', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'transaction' => $transaction,
        ]);
    }

    public function store(Request $request)
    {
        $error = false;
        $bills = $request->bills;
        $transaction = (object) [];

        if (empty($bills)) {
            $error = __('string.not_bill_selected');
        }

        if ($error == false) {
            $status = '';
            $subtotal = 0;
            $bills_id = [];
            $student = $request->id_student;

            // Hitung total murni dari tagihan yang dicentang
            foreach ($bills as $id => $nominal) {
                $subtotal += $nominal;
                array_push($bills_id, $id);

                $trans_bill = TransactionBill::select('id_student')->whereId($id)->first();
                if ($trans_bill->id_student != $student) {
                    Log::alert('-----');
                    Log::alert('Payment/store - failed');
                    Log::alert('Message : ID Student pada bills tidak sama dengan ID student yang dipilih');
                    Log::alert('Data : '.json_encode($request->all()));
                    $error = __('string.something_went_wrong');
                    break;
                }
            }

            // --- AWAL LOGIKA CICILAN ---
            $is_cicilan = $request->is_cicilan == 1;
            $cicilan_nominal = floatval($request->cicilan_nominal);

            // Tentukan nominal yang BENAR-BENAR dibayar (Actual Subtotal)
            if ($is_cicilan && $cicilan_nominal > 0) {
                // Pastikan nominal cicilan tidak melebihi jumlah tagihan yg dicentang
                $actual_subtotal = min($cicilan_nominal, $subtotal);
            } else {
                $actual_subtotal = $subtotal;
            }
            // --- AKHIR LOGIKA CICILAN ---

            if ($request->payment_method == TransactionMethod::TopupBalance->value) {
                // Cek saldo orang tua menggunakan nominal actual_subtotal
                if ($actual_subtotal > Auth::user()->parent->balance) {
                    $error = __('string.balance_insufficient');
                }
            }

            if ($error == false) {
                $pending = Transaction::with(['student' => fn ($query) => $query->select('id')])
                    ->whereHas('student', fn ($query) => $query->whereIdParent(Auth::user()->parent->id))
                    ->tagihan()
                    ->notPaid()
                    ->count();

                if ($pending > 0) {
                    $error = strip_tags(__('string.you_have_payment_pending'));
                }
            }

            if ($error == false) {
                DB::transaction(function () use ($request, $actual_subtotal, $bills, &$transaction, &$status) {
                    $status = TransactionStatus::NotPaid->value;

                    $merge = [
                        'dates' => date('Y-m-d'),
                        'subtotal' => $actual_subtotal,
                        'total' => $actual_subtotal + $request->unique_code,
                        'flag' => TransactionFlag::Tagihan->value,
                    ];

                    if ($request->payment_method == TransactionMethod::TopupBalance->value) {
                        $status = TransactionStatus::Paid->value;
                        $merge['status'] = $status;
                        $merge['paid_at'] = date('Y-m-d H:i:s');
                        $merge['paid_by'] = 0;
                        $merge['unique_code'] = 0;
                        $merge['total'] = $actual_subtotal;
                    } else {
                        TransactionPaymentCode::whereCode($request->unique_code)->update(['status' => PaymentCodeStatus::Used->value]);
                    }

                    $request->merge($merge);
                    $transaction = Transaction::create($request->all());

                    // --- ALGORITMA WATERFALL & SPLIT BILL ---
                    $remaining_payment = $actual_subtotal;
                    $processed_bills_ids = [];

                    foreach ($bills as $b_id => $b_nominal) {
                        if ($remaining_payment <= 0) {
                            break;
                        } // Hentikan jika uang terdistribusi habis

                        // PENTING: Jangan gunakan select('id', ...) di sini, panggil seluruh kolom
                        // Tujuannya agar saat replicate() (duplikat), data bulan, tahun, & semester ikut tercopy
                        $trans_bill = TransactionBill::with([
                            'bill' => fn ($query) => $query->select('id', 'id_type', 'id_year'),
                            'student' => function ($query) {
                                $query->select('id', 'id_class')
                                    ->with(['class' => fn ($qc) => $qc->select('id', 'level_education', 'level_class')]);
                            },
                        ])->find($b_id);

                        if (! $trans_bill) {
                            continue;
                        }

                        // Tentukan berapa yang mampu dibayar untuk tagihan ini
                        $pay_amount = min($remaining_payment, $trans_bill->total);

                        if ($pay_amount == $trans_bill->total) {
                            // LUNAS PENUH (Untuk tagihan ini)
                            $trans_bill->update([
                                'id_transaction' => $transaction->id,
                                'status' => $status,
                            ]);

                            if ($status == TransactionStatus::Paid->value) {
                                TransactionBill::updateReport($trans_bill, $transaction->paid_at);
                            }
                        } else {
                            // CICILAN (PEMBAYARAN SEBAGIAN) -> SPLIT BILL
                            $original_total = $trans_bill->total;
                            $original_subtotal = $trans_bill->subtotal;

                            // Langkah 1: Ubah nominal tagihan SAAT INI menjadi sejumlah uang cicilan
                            $trans_bill->update([
                                'total' => $pay_amount,
                                'subtotal' => $pay_amount,
                                'id_transaction' => $transaction->id,
                                'status' => $status,
                            ]);

                            if ($status == TransactionStatus::Paid->value) {
                                TransactionBill::updateReport($trans_bill, $transaction->paid_at);
                            }

                            // Langkah 2: DUPLIKAT baris untuk membuat sisa tagihannya
                            $new_bill = $trans_bill->replicate();
                            $new_bill->id_transaction = null;
                            $new_bill->status = TransactionStatus::NotPaid->value; // Kembalikan statusnya ke 0 (Belum bayar)
                            $new_bill->total = $original_total - $pay_amount;
                            $new_bill->subtotal = $original_subtotal - $pay_amount;
                            $new_bill->created_at = now();
                            $new_bill->save();
                        }

                        array_push($processed_bills_ids, $b_id);
                        $remaining_payment -= $pay_amount;
                    }

                    // Update Array tagihan yang terproses ke tabel Transaksi
                    $transaction->update(['bills' => $processed_bills_ids]);

                    // --- POTONG SALDO (Khusus Topup) ---
                    if ($request->payment_method == TransactionMethod::TopupBalance->value) {
                        $parent = Parents::select('id', 'balance')->whereId(Auth::user()->parent->id)->first();
                        $parent->balance -= $transaction->subtotal; // Memotong saldo berdasarkan nominal asli cicilan
                        $parent->save();

                        TopupHistory::create([
                            'id_parent' => $parent->id,
                            'id_transaction' => $transaction->id,
                            'description' => 'Pembayaran Tagihan #'.$transaction->number,
                            'credit' => $transaction->subtotal,
                            'balance' => $parent->balance,
                        ]);

                        event(new TransactionBillPaid($transaction));
                    }
                });
            }
        }

        if ($error == false) {
            if ($status == TransactionStatus::Paid->value) {
                $response = [
                    'status' => true,
                    'message' => __('message.payment_success', ['label' => __($this->title)]),
                    'data' => [
                        'redirect' => route('finance.payment.show', $transaction->encrypted_id),
                    ],
                ];
            } else {
                $response = [
                    'status' => true,
                    'message' => __('message.process_success', ['label' => __($this->title)]),
                    'data' => [
                        'redirect' => route('finance.payment.waiting', $transaction->encrypted_id),
                    ],
                ];
            }
        } else {
            $response = [
                'status' => false,
                'message' => $error,
            ];
        }

        return response()->json($response);
    }

    // Used for :
    // - resources/views/backend/finance/payment/waiting.blade.php
    // - resources/views/backend/finance/balance/waiting.blade.php
    public function check(Transaction $transaction)
    {
        if ($transaction->is_tagihan) {
            $redirect = route('finance.payment.index');
        } elseif ($transaction->is_setor_tabungan) {
            $redirect = route('finance.savings.index');
        } else { // Topup Saldo
            $redirect = route('finance.balance.index');
        }

        Log::channel('payment')->info('-----');
        Log::channel('payment')->info('Moota/check');
        Log::channel('payment')->info('Headers : '.json_encode($transaction->toArray()));
        Log::channel('payment')->info('-----');

        if ($transaction->status->value == TransactionStatus::Paid->value) {
            $response = [
                'status' => true,
                'message' => __('string.payment_received'),
                'data' => [
                    'redirect' => $redirect,
                ],
            ];
        } else {
            $response = [
                'status' => false,
                'message' => __('string.payment_not_received'),
            ];
        }

        return response()->json($response);
    }

    // Used for cancel all transaction
    // - Tagihan
    // - Setoran Tabungan
    // - Topup Saldo
    public function destroy(Transaction $transaction)
    {
        if ($transaction->is_tagihan) {
            $label = __($this->title);
            $redirect = route('finance.payment.index');

            TransactionBill::whereIdTransaction($transaction->id)->update(['id_transaction' => null]);
        } elseif ($transaction->is_setor_tabungan) {
            $label = __('label.savings_deposit');
            $redirect = route('finance.savings.index');
        } else { // Topup Saldo
            $label = __('label.topup_balance');
            $redirect = route('finance.balance.index');
        }

        $transaction->delete();

        $response = [
            'status' => true,
            'message' => __('message.cancel_success', ['label' => $label]),
            'data' => [
                'redirect' => $redirect,
            ],
        ];

        return response()->json($response);
    }

    public function getBills(Request $request)
    {
        $student = $request->student;
        $pending = Transaction::select('id', 'id_student', 'number', 'total', 'payment_method')
            ->with(['student' => fn ($query) => $query->select('id')])
            ->whereHas('student', fn ($query) => $query->whereIdParent(Auth::user()->parent->id))
            ->tagihan()
            ->notPaid()
            ->first();

        if (empty($pending)) {
            $transbill = TransactionBill::select('id', 'id_bill', 'semester', 'months', 'years', 'total')
                ->with([
                    'bill' => function ($query) {
                        $query->select('id', 'id_type', 'name')
                            ->with([
                                'type' => fn ($qt) => $qt->select('id', 'period'),
                            ]);
                    },
                ])
                ->whereIdStudent($student)
                ->whereNull('id_transaction')
                ->notPaid()
                ->orderBy('due_date')
                ->get();

            $bill = [];
            $bill_list = [];

            foreach ($transbill as $t) {
                $discount = 0;
                $bill_discount = BillDiscount::select('id', 'id_bill', 'applies_to', 'nominal')
                    ->whereIdStudent($student)
                    ->whereIdBill($t->bill->id)
                    ->first();

                if (! empty($bill_discount)) {
                    if (empty($bill_discount->applies_to)) {
                        $discount = $bill_discount->nominal;
                    } else {
                        $applies = json_decode(json_encode($bill_discount->applies_to), true);

                        if ($t->bill->type->is_period_monthly) {
                            $month = $t->years.'-'.Str::padLeft($t->months, 2, '0');
                            if (array_key_exists($month, $applies)) {
                                $discount = $bill_discount->nominal;
                            }
                        } else {
                            if (array_key_exists($t->semester, $applies)) {
                                $discount = $bill_discount->nominal;
                            }
                        }
                    }
                }

                $total = $t->total - $discount;

                $bill_list[$t->id] = [
                    'nominal' => $total,
                    'id_type' => $t->bill->id_type,
                ];

                array_push($bill, (object) [
                    'id' => $t->id,
                    'months' => $t->months,
                    'years' => $t->years,
                    'semester' => $t->semester,
                    'name' => $t->bill->name,
                    'period' => $t->bill->type->period->value,
                    'total' => $total,
                ]);
            }

            $period_monthly = BillPeriod::Monthly->value;
            $period_semester = BillPeriod::Semiannual->value;

            $bills = view($this->path.'get-bill', [
                'bill' => $bill,
                'bill_end' => count($transbill) - 1,
                'period' => (object) [
                    'monthly' => $period_monthly,
                    'semester' => $period_semester,
                ],
            ])->render();

            $report = ReportStudent::select('bill_paid', 'bill_not_paid')->whereIdStudent($student)->first();
            $count_paid = 0;
            $count_not_paid = 0;

            if (! empty($report)) {
                $count_paid = $report->bill_paid;
                $count_not_paid = $report->bill_not_paid;
            }

            $response = [
                'status' => true,
                'message' => 'Ok',
                'data' => [
                    'pending' => false,
                    'bills' => $bills,
                    'bills_list' => $bill_list,
                    'count' => [
                        'paid' => $count_paid,
                        'not_paid' => $count_not_paid,
                    ],
                ],
            ];
        } else {
            $report = ReportStudent::select('bill_paid', 'bill_not_paid')->whereIdStudent($student)->first();
            $count_paid = 0;
            $count_not_paid = 0;

            if (! empty($report)) {
                $count_paid = $report->bill_paid;
                $count_not_paid = $report->bill_not_paid;
            }

            $response = [
                'status' => true,
                'message' => 'Ok',
                'data' => [
                    'pending' => true,
                    'number' => $pending->number,
                    'total' => number_format($pending->total, 0, '', '.'),
                    'image' => $pending->method->image_payment,
                    'url' => route('finance.payment.waiting', $pending->encrypted_id),
                    'count' => [
                        'paid' => $count_paid,
                        'not_paid' => $count_not_paid,
                    ],
                ],
            ];
        }

        return response()->json($response);
    }

    public function getHistory(Request $request)
    {
        $page = $request->page;
        $limit = 5;
        $offset = ($page - 1) * $limit;
        $parent = Auth::user()->parent->id;
        $period_monthly = BillPeriod::Monthly->value;
        $period_semester = BillPeriod::Semiannual->value;

        $transaction = Transaction::select('id', 'id_student', 'number', 'payment_method', 'paid_at', 'total', 'created_at', 'status')
            ->with(['student' => fn ($query) => $query->select('id')])
            ->whereHas('student', function ($query) use ($parent) {
                $query->whereIdParent($parent);
            })
            ->tagihan();

        if (! empty($request->search)) {
            $search = $request->search;
            $transaction = $transaction->where(function ($query) use ($search) {
                $query->where('number', 'like', '%'.$search.'%')
                    ->orWhere('paid_at', 'like', '%'.$search.'%')
                    ->orWhere('created_at', 'like', '%'.$search.'%');
            });
        }

        $transaction = $transaction->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $list = view($this->path.'get-history', [
            'transaction' => $transaction,
            'period' => (object) [
                'monthly' => $period_monthly,
                'semester' => $period_semester,
            ],
        ])->render();

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'count' => $transaction->count(),
                'list' => $list,
            ],
        ];

        return response()->json($response);
    }
}
