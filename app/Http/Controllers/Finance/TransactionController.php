<?php

namespace App\Http\Controllers\Finance;

use App\Enums\BillPeriod;
use App\Enums\DepositStatus;
use App\Enums\TransactionDepositStatus;
use App\Enums\TransactionFlag;
use App\Enums\TransactionMethod;
use App\Enums\TransactionStatus;
use App\Events\SavingsDepositPaid;
use App\Events\TransactionBillPaid;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionCashRequest;
use App\Http\Requests\TransactionCashVerifyRequest;
use App\Http\Requests\TransactionUniqueCodeRequest;
use App\Http\Requests\TransactionUniqueCodeVerifyRequest;
use App\Models\BillDiscount;
use App\Models\CashDeposit;
use App\Models\Classroom;
use App\Models\Donation;
use App\Models\DonationHistory;
use App\Models\Parents;
use App\Models\SavingsWithdrawal;
use App\Models\Scopes\BranchScope;
use App\Models\Student;
use App\Models\TopupHistory;
use App\Models\Transaction;
use App\Models\TransactionBill;
use App\Models\TransactionPaymentCode;
use App\Models\UniqueCodeDeposit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    private $title_prefix = 'label.transaction';

    private $title = [
        'payment' => 'label.payment',
        'history' => 'label.history',
        'pending' => 'label.pending',
        'savings-deposit' => 'label.bill',
        'cash' => 'label.cash_deposit',
        'unique-code' => 'label.unique_code_deposit',
    ];

    private $icon = 'bx bx-receipt';

    private $path = [
        'transaction' => 'backend.finance.transaction.',
        'bill' => 'backend.finance.transaction.bill.',
        'pending' => 'backend.finance.transaction.pending.',
        'cash' => 'backend.finance.transaction.cash.',
        'unique-code' => 'backend.finance.transaction.unique-code.',
    ];

    public function bill()
    {
        $number = Transaction::generateNumber(TransactionFlag::Tagihan->value);
        $yesno = Common::option('yesno');

        return view($this->path['bill'].'index', [
            'title' => __($this->title_prefix).' - '.__($this->title['payment']),
            'icon' => $this->icon,
            'number' => $number,
            'yesno' => $yesno,
        ]);
    }

    public function pending()
    {
        $types = [
            TransactionFlag::Tagihan->value => __('label.bill'),
            TransactionFlag::SetorTabungan->value => __('label.savings_deposit'),
            TransactionFlag::TopupSaldo->value => __('label.topup_balance'),
        ];

        $type_bill = TransactionFlag::Tagihan->value;
        $type_topup = TransactionFlag::TopupSaldo->value;

        return view($this->path['pending'].'index', [
            'title' => __($this->title_prefix).' - '.__($this->title['pending']),
            'icon' => $this->icon,
            'types' => $types,
            'type_bill' => $type_bill,
            'type_topup' => $type_topup,
        ]);
    }

    public function cash($render)
    {
        return view($this->path['cash'].$render, [
            'title' => __($this->title_prefix).' - '.__($this->title['cash']),
            'icon' => $this->icon,
            'path' => $this->path['cash'],
            'render' => $render,
        ]);
    }

    public function uniqueCode($render)
    {
        return view($this->path['unique-code'].$render, [
            'title' => __($this->title_prefix).' - '.__($this->title['unique-code']),
            'icon' => $this->icon,
            'path' => $this->path['unique-code'],
            'render' => $render,
        ]);
    }

    public function history(Request $request)
    {
        $filter_start = (empty($request->start)) ? date('Y-m').'-01' : $request->start;
        $filter_end = (empty($request->end)) ? date('Y-m-t') : $request->end;
        $types = [
            TransactionFlag::Tagihan->value => __('label.bill'),
            TransactionFlag::SetorTabungan->value => __('label.savings_deposit'),
            TransactionFlag::PengambilanTabungan->value => __('label.savings_withdrawal'),
            TransactionFlag::TopupSaldo->value => __('label.topup_balance'),
        ];

        $type_bill = TransactionFlag::Tagihan->value;
        $type_topup = TransactionFlag::TopupSaldo->value;
        $type_withdrawal = TransactionFlag::PengambilanTabungan->value;

        return view($this->path['transaction'].'history', [
            'title' => __($this->title_prefix).' - '.__('label.history'),
            'icon' => $this->icon,
            'types' => $types,
            'type_bill' => $type_bill,
            'type_topup' => $type_topup,
            'type_withdrawal' => $type_withdrawal,
            'filter' => (object) [
                'start' => $filter_start,
                'end' => $filter_end,
            ],
        ]);
    }

    public function verifyCash(CashDeposit $deposit)
    {
        $status = [
            DepositStatus::Accepted->value => __('label.accepted'),
            DepositStatus::Rejected->value => __('label.rejected'),
        ];

        $status_rejected = DepositStatus::Rejected->value;

        return view($this->path['cash'].'verify', [
            'title' => __($this->title_prefix).' - '.__($this->title['cash']),
            'icon' => $this->icon,
            'deposit' => $deposit,
            'status' => $status,
            'status_rejected' => $status_rejected,
        ]);
    }

    public function verifyUniqueCode(UniqueCodeDeposit $deposit)
    {
        $status = [
            DepositStatus::Accepted->value => __('label.accepted'),
            DepositStatus::Rejected->value => __('label.rejected'),
        ];

        $status_rejected = DepositStatus::Rejected->value;

        return view($this->path['unique-code'].'verify', [
            'title' => __($this->title_prefix).' - '.__($this->title['unique-code']),
            'icon' => $this->icon,
            'deposit' => $deposit,
            'status' => $status,
            'status_rejected' => $status_rejected,
        ]);
    }

    public function showBill(Transaction $transaction)
    {
        $method_cash = TransactionMethod::Cash->value;
        $period_monthly = BillPeriod::Monthly->value;
        $period_semester = BillPeriod::Semiannual->value;
        $bills = (object) [];
        $withdrawals = (object) [];

        if ($transaction->is_tagihan) {
            $bills = TransactionBill::select('id', 'id_bill', 'semester', 'months', 'years', 'subtotal', 'discount', 'total')
                ->with([
                    'bill' => function ($query) {
                        $query->select('id', 'id_year', 'id_type', 'name')
                            ->with([
                                'year' => fn ($qy) => $qy->select('id', 'start_year', 'end_year'),
                                'type' => fn ($qt) => $qt->select('id', 'name', 'period'),
                            ]);
                    },
                ])
                ->whereIdTransaction($transaction->id)
                ->orderBy('due_date')
                ->get();
        } elseif ($transaction->is_pengambilan_tabungan) {
            // $withdrawals = SavingsWithdrawal::select('id', 'id_student', 'number', 'dates', 'total')
            //     ->with([
            //         'student' => function($query) {
            //             $query->select('id', 'id_class', 'nis', 'name')
            //                 ->with(['class' => fn($qc) => $qc->select('id', 'name')]);
            //         }
            //     ])
            //     ->whereIn('id', $transaction->bills)
            //     ->get();
            $table_savings = (new SavingsWithdrawal)->getTable();
            $table_student = (new Student)->getTable();
            $table_class = (new Classroom)->getTable();
            $withdrawals = SavingsWithdrawal::select($table_savings.'.id', $table_savings.'.number', $table_savings.'.dates',
                $table_savings.'.total', $table_student.'.nis', $table_student.'.name', $table_class.'.name AS class_name')
                ->join($table_student, $table_student.'.id', '=', $table_savings.'.id_student')
                ->join($table_class, $table_class.'.id', '=', $table_student.'.id_class')
                ->whereIn($table_savings.'.id', $transaction->bills)
                ->where($table_savings.'.branch_id', Auth::user()->branch_id)
                ->withoutGlobalScope(BranchScope::class)
                ->orderBy($table_class.'.name')
                ->get();
        }

        return view($this->path['bill'].'show', [
            'title' => __($this->title_prefix).' - '.__($this->title['history']),
            'icon' => $this->icon,
            'transaction' => $transaction,
            'method_cash' => $method_cash,
            'bills' => $bills,
            'withdrawals' => $withdrawals,
            'period' => (object) [
                'monthly' => $period_monthly,
                'semester' => $period_semester,
            ],
        ]);
    }

    public function datatableDonatur(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $donation = Donation::select('id', 'name', 'total', 'used', 'remaining')->whereRaw('used<total');
        $donation_count = $donation->count();
        $donation_filter = $donation->where('name', 'like', '%'.$search.'%');
        $donation_count_filter = $donation_filter->count();

        $donation_data = $donation_filter->limit($limit)
            ->offset($start)
            ->orderBy('used')
            ->get();

        $donation_arr = [];

        foreach ($donation_data as $d) {
            $push = $d->toArray();
            $push['encrypted_id'] = $d->encrypted_id;

            array_push($donation_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $donation_count,
            'recordsFiltered' => $donation_count_filter,
            'data' => $donation_arr,
        ]);
    }

    // Datatable for Transaction list to Deposit
    public function datatablePaid(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $selected = (empty($request->selected)) ? [] : $request->selected;
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $transaction = Transaction::select('id', 'id_student', 'id_parent', 'number', 'dates', 'subtotal', 'donation', 'unique_code',
            'total', 'payment_method', 'paid_at')
            ->with(['student' => fn ($query) => $query->select('id', 'nis', 'name')])
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date == $end_date) {
                    $query->whereDates($start_date);
                } else {
                    $query->whereBetween('dates', [$start_date, $end_date]);
                }
            })
            ->paid()
            ->tagihan();

        if ($request->has('edit')) {
            $transaction = $transaction->where(function ($query) use ($request) {
                $query->whereStatusDeposit(TransactionDepositStatus::NotDeposit->value)
                    ->orWhereIn('id', $request->edit);
            });
        } else {
            $transaction = $transaction->notDeposit();
        }

        $transaction_count = $transaction->count();

        if (empty($search)) {
            $transaction_filter = $transaction;
        } else {
            $transaction_filter = $transaction->where(function ($query) use ($search) {
                $query->where('number', 'like', '%'.$search.'%')
                    ->orWhere('dates', 'like', '%'.$search.'%')
                    ->orWhereHas('student', function ($qs) use ($search) {
                        $qs->where('nis', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('parent', function ($qd) use ($search) {
                        $qd->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        $transaction_count_filter = $transaction_filter->count();
        $transaction_data = $transaction_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $transaction_arr = [];

        foreach ($transaction_data as $t) {
            $push = $t->toArray();
            $push['encrypted_id'] = $t->encrypted_id;
            $push['method_name'] = $t->method->name;
            $push['checked'] = (in_array($t->id, $selected)) ? ' checked' : '';
            $push['total_deposit'] = $t->total - $t->unique_code;

            array_push($transaction_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $transaction_count,
            'recordsFiltered' => $transaction_count_filter,
            'data' => $transaction_arr,
        ]);
    }

    // Datatable for Transaction list that have Unique Code to Deposit
    public function datatablePaidUniqueCode(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $selected = (empty($request->selected)) ? [] : $request->selected;

        $transaction = Transaction::select('id', 'id_student', 'id_parent', 'number', 'dates', 'subtotal', 'donation', 'unique_code',
            'total', 'payment_method', 'flag', 'paid_at')
            ->with([
                'student' => fn ($query) => $query->select('id', 'nis', 'name'),
                'parent' => fn ($query) => $query->select('id', 'name', 'phone'),
            ])
            ->where('flag', '!=', TransactionFlag::PengambilanTabungan->value)
            ->where('unique_code', '>', 0)
            ->paid()
            ->tagihan();

        if ($request->has('edit')) {
            $transaction = $transaction->where(function ($query) use ($request) {
                $query->whereStatusDepositCode(TransactionDepositStatus::NotDeposit->value)
                    ->orWhereIn('id', $request->edit);
            });
        } else {
            $transaction = $transaction->notDepositCode();
        }

        if (! empty($request->start_date)) {
            $start_date = $request->start_date;
            $end_date = $request->end_date;

            $transaction = $transaction->where(function ($query) use ($start_date, $end_date) {
                if ($start_date == $end_date) {
                    $query->whereDates($start_date);
                } else {
                    $query->whereBetween('dates', [$start_date, $end_date]);
                }
            });
        }

        if (! empty($request->type)) {
            $transaction = $transaction->whereFlag($request->type);
        }

        $transaction_count = $transaction->count();

        if (empty($search)) {
            $transaction_filter = $transaction;
        } else {
            $transaction_filter = $transaction->where(function ($query) use ($search) {
                $query->where('number', 'like', '%'.$search.'%')
                    ->orWhere('dates', 'like', '%'.$search.'%')
                    ->orWhereHas('student', function ($qs) use ($search) {
                        $qs->where('nis', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('parent', function ($qd) use ($search) {
                        $qd->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        $transaction_count_filter = $transaction_filter->count();
        $transaction_data = $transaction_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $transaction_arr = [];

        foreach ($transaction_data as $t) {
            $push = $t->toArray();
            $push['encrypted_id'] = $t->encrypted_id;
            $push['method_name'] = $t->method->name;
            $push['flag_detail'] = $t->flag_detail;
            $push['checked'] = (in_array($t->id, $selected)) ? ' checked' : '';

            array_push($transaction_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $transaction_count,
            'recordsFiltered' => $transaction_count_filter,
            'data' => $transaction_arr,
        ]);
    }

    public function datatablePending(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $transaction = Transaction::select('id', 'id_student', 'id_parent', 'number', 'dates', 'subtotal', 'donation', 'unique_code',
            'total', 'payment_method', 'flag')
            ->with([
                'student' => fn ($query) => $query->select('id', 'nis', 'name'),
                'parent' => fn ($query) => $query->select('id', 'name', 'phone'),
            ])
            ->notPaid();

        if (! empty($request->type)) {
            $transaction = $transaction->whereFlag($request->type);
        }

        $transaction_count = $transaction->count();

        if (empty($search)) {
            $transaction_filter = $transaction;
        } else {
            $transaction_filter = $transaction->where(function ($query) use ($search) {
                $query->where('number', 'like', '%'.$search.'%')
                    ->orWhere('dates', 'like', '%'.$search.'%')
                    ->orWhereHas('student', function ($qs) use ($search) {
                        $qs->where('nis', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('parent', function ($qd) use ($search) {
                        $qd->where('name', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%');
                    });
            });
        }

        $transaction_count_filter = $transaction_filter->count();
        $transaction_data = $transaction_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $transaction_arr = [];

        foreach ($transaction_data as $t) {
            $push = $t->toArray();
            $push['encrypted_id'] = $t->encrypted_id;
            $push['method_name'] = $t->method->name;
            $push['flag_detail'] = $t->flag_detail;

            array_push($transaction_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $transaction_count,
            'recordsFiltered' => $transaction_count_filter,
            'data' => $transaction_arr,
        ]);
    }

    public function datatableHistory(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $transaction = Transaction::select('id', 'id_parent', 'id_student', 'id_donation', 'number', 'dates', 'subtotal', 'donation', 'total',
            'payment_method', 'paid_at', 'flag', 'unique_code')
            ->with([
                'student' => fn ($query) => $query->select('id', 'nis', 'name'),
                'donatur' => fn ($query) => $query->select('id', 'name'),
                'parent' => fn ($query) => $query->select('id', 'name', 'phone'),
                'personResponsible' => fn ($query) => $query->select('id', 'name', 'phone'),
            ])
            ->where(function ($query) use ($request) {
                $query->whereBetween('dates', [$request->start_date, $request->end_date]);
            })
            ->paid();

        if (! empty($request->type)) {
            $transaction = $transaction->whereFlag($request->type);
        }

        $transaction_count = $transaction->count();

        if (empty($search)) {
            $transaction_filter = $transaction;
        } else {
            $transaction_filter = $transaction->where(function ($query) use ($search) {
                $query->where('number', 'like', '%'.$search.'%')
                    ->orWhere('dates', 'like', '%'.$search.'%')
                    ->orWhereHas('student', function ($qs) use ($search) {
                        $qs->where('nis', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('donatur', function ($qd) use ($search) {
                        $qd->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('parent', function ($qd) use ($search) {
                        $qd->where('name', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('personResponsible', function ($qd) use ($search) {
                        $qd->where('name', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%');
                    });
            });
        }

        $transaction_count_filter = $transaction_filter->count();
        $transaction_data = $transaction_filter->limit($limit)
            ->offset($start)
            ->orderBy('paid_at', 'desc')
            ->get();

        $transaction_arr = [];

        foreach ($transaction_data as $t) {
            $push = $t->toArray();
            $push['encrypted_id'] = $t->encrypted_id;
            $push['method_name'] = $t->method->name;
            $push['flag_detail'] = $t->flag_detail;

            array_push($transaction_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $transaction_count,
            'recordsFiltered' => $transaction_count_filter,
            'data' => $transaction_arr,
        ]);
    }

    public function datatableCash(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        switch ($request->render) {
            case 'accepted':
                $status = DepositStatus::Accepted->value;
                break;

            case 'rejected':
                $status = DepositStatus::Rejected->value;
                break;

            default:
                $status = DepositStatus::Waiting->value;
        }

        $deposit = CashDeposit::select('id', 'number', 'dates', 'total', 'verified_at', 'verified_by', 'reason', 'created_at')
            ->with([
                'verificator' => fn ($query) => $query->select('id', 'name'),
            ])
            ->whereStatus($status);

        if (Auth::user()->is_kasir) {
            $deposit = $deposit->whereCreatedBy(Auth::id());
        }

        $deposit_count = $deposit->count();

        if (empty($search)) {
            $deposit_filter = $deposit;
        } else {
            $deposit_filter = $deposit->where(function ($query) use ($search) {
                $query->where('number', 'like', '%'.$search.'%')
                    ->orWhere('dates', 'like', '%'.$search.'%')
                    ->orWhereHas('verificator', function ($qd) use ($search) {
                        $qd->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        $order = ($status == DepositStatus::Waiting->value) ? 'created_at' : 'verified_at';
        $deposit_count_filter = $deposit_filter->count();
        $deposit_data = $deposit_filter->limit($limit)
            ->offset($start)
            ->orderBy($order, 'desc')
            ->get();

        $deposit_arr = [];

        foreach ($deposit_data as $t) {
            $push = $t->toArray();
            $push['encrypted_id'] = $t->encrypted_id;

            array_push($deposit_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $deposit_count,
            'recordsFiltered' => $deposit_count_filter,
            'data' => $deposit_arr,
        ]);
    }

    public function datatableUniqueCode(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        switch ($request->render) {
            case 'accepted':
                $status = DepositStatus::Accepted->value;
                break;

            case 'rejected':
                $status = DepositStatus::Rejected->value;
                break;

            default:
                $status = DepositStatus::Waiting->value;
        }

        $deposit = UniqueCodeDeposit::select('id', 'number', 'dates', 'total', 'verified_at', 'verified_by', 'reason', 'created_at')
            ->with([
                'verificator' => fn ($query) => $query->select('id', 'name'),
            ])
            ->whereStatus($status);

        if (Auth::user()->is_kasir) {
            $deposit = $deposit->whereCreatedBy(Auth::id());
        }

        $deposit_count = $deposit->count();

        if (empty($search)) {
            $deposit_filter = $deposit;
        } else {
            $deposit_filter = $deposit->where(function ($query) use ($search) {
                $query->where('number', 'like', '%'.$search.'%')
                    ->orWhere('dates', 'like', '%'.$search.'%')
                    ->orWhereHas('verificator', function ($qd) use ($search) {
                        $qd->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        $order = ($status == DepositStatus::Waiting->value) ? 'created_at' : 'verified_at';
        $deposit_count_filter = $deposit_filter->count();
        $deposit_data = $deposit_filter->limit($limit)
            ->offset($start)
            ->orderBy($order, 'desc')
            ->get();

        $deposit_arr = [];

        foreach ($deposit_data as $t) {
            $push = $t->toArray();
            $push['encrypted_id'] = $t->encrypted_id;

            array_push($deposit_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $deposit_count,
            'recordsFiltered' => $deposit_count_filter,
            'data' => $deposit_arr,
        ]);
    }

    public function createCash()
    {
        $types = [
            TransactionFlag::Tagihan->value => __('label.bill'),
            TransactionFlag::SetorTabungan->value => __('label.savings_deposit'),
            TransactionFlag::TopupSaldo->value => __('label.topup_balance'),
        ];

        $type_bill = TransactionFlag::Tagihan->value;
        $type_topup = TransactionFlag::TopupSaldo->value;

        return view($this->path['cash'].'create', [
            'title' => __($this->title_prefix).' - '.__($this->title['cash']),
            'icon' => $this->icon,
            'types' => $types,
            'type_bill' => $type_bill,
            'type_topup' => $type_topup,
        ]);
    }

    public function createUniqueCode()
    {
        $types = [
            TransactionFlag::Tagihan->value => __('label.bill'),
            TransactionFlag::SetorTabungan->value => __('label.savings_deposit'),
            TransactionFlag::TopupSaldo->value => __('label.topup_balance'),
        ];

        $type_bill = TransactionFlag::Tagihan->value;
        $type_topup = TransactionFlag::TopupSaldo->value;

        return view($this->path['unique-code'].'create', [
            'title' => __($this->title_prefix).' - '.__($this->title['unique-code']),
            'icon' => $this->icon,
            'types' => $types,
            'type_bill' => $type_bill,
            'type_topup' => $type_topup,
        ]);
    }

    public function store(Request $request)
    {
        $error = false;
        $bills = $request->bills;

        if (empty($bills)) {
            $error = __('string.not_bill_selected');
        }
        if (empty($request->dates)) {
            $error = __('validation.required', ['attribute' => __('label.transaction_date')]);
        }

        if ($error == false) {

            DB::transaction(function () use ($request, $bills) {
                $is_cicilan = $request->is_cicilan == 1;
                $cicilan_nominal = floatval($request->cicilan_nominal);

                $total_selected_bills = 0;
                foreach ($bills as $id => $nominal) {
                    $total_selected_bills += $nominal;
                }

                if ($is_cicilan && $cicilan_nominal > 0) {
                    $actual_subtotal = min($cicilan_nominal, $total_selected_bills);
                } else {
                    $actual_subtotal = $total_selected_bills;
                }

                $donation = 0;
                $id_donation = null;
                if (! empty($request->id_donation)) {
                    $donation = (float) str_replace('.', '', $request->donation);
                    $id_donation = Crypt::decrypt($request->id_donation);
                }

                $actual_total = $actual_subtotal - $donation;

                $merge = [
                    'payment_method' => TransactionMethod::Cash->value,
                    'paid_at' => date('Y-m-d H:i:s'),
                    'paid_by' => Auth::id(),
                    'status' => TransactionStatus::Paid->value,
                    'flag' => TransactionFlag::Tagihan->value,
                    'subtotal' => $actual_subtotal,
                    'total' => $actual_total,
                    'id_donation' => $id_donation,
                    'donation' => $donation,
                ];

                $request->merge($merge);
                $transaction = Transaction::create($request->all());

                $remaining_payment = $actual_subtotal;
                $processed_bills_ids = [];
                $donation_descr = [];

                foreach ($bills as $b_id => $b_nominal) {
                    if ($remaining_payment <= 0) {
                        break;
                    }

                    $trans_bill = TransactionBill::with([
                        'bill' => fn ($query) => $query->select('id', 'id_type', 'id_year', 'name')->with(['type' => fn ($qt) => $qt->select('id', 'name', 'period')]),
                        'student' => function ($query) {
                            $query->select('id', 'id_class')->with(['class' => fn ($qc) => $qc->select('id', 'level_education', 'level_class')]);
                        },
                    ])->find($b_id);

                    if (! $trans_bill) {
                        continue;
                    }

                    $pay_amount = min($remaining_payment, $trans_bill->total);

                    if ($id_donation) {
                        $time = '';
                        if ($trans_bill->bill->type->is_period_monthly) {
                            $time = Common::monthFormat($trans_bill->months).' '.$trans_bill->years;
                        } elseif ($trans_bill->bill->type->is_period_semiannual) {
                            $time = 'Semester '.$trans_bill->semester;
                        }

                        $partial_text = ($pay_amount < $trans_bill->total) ? ' (Sebagian/Cicilan)' : '';
                        array_push($donation_descr, [
                            'type' => $trans_bill->bill->type->name,
                            'name' => $trans_bill->bill->name.$partial_text,
                            'time' => $time,
                        ]);
                    }

                    if ($pay_amount == $trans_bill->total) {
                        $trans_bill->update([
                            'id_transaction' => $transaction->id,
                            'status' => TransactionStatus::Paid->value,
                        ]);
                        TransactionBill::updateReport($trans_bill, $transaction->paid_at);
                    } else {
                        $original_total = $trans_bill->total;
                        $original_subtotal = $trans_bill->subtotal;

                        $trans_bill->update([
                            'total' => $pay_amount,
                            'subtotal' => $pay_amount,
                            'id_transaction' => $transaction->id,
                            'status' => TransactionStatus::Paid->value,
                        ]);
                        TransactionBill::updateReport($trans_bill, $transaction->paid_at);

                        $new_bill = $trans_bill->replicate();
                        $new_bill->id_transaction = null;
                        $new_bill->status = 0;
                        $new_bill->total = $original_total - $pay_amount;
                        $new_bill->subtotal = $original_subtotal - $pay_amount;
                        $new_bill->created_at = now();
                        $new_bill->save();
                    }

                    array_push($processed_bills_ids, $b_id);
                    $remaining_payment -= $pay_amount;
                }

                $transaction->update(['bills' => $processed_bills_ids]);

                if ($id_donation) {
                    $donation_model = Donation::select('id', 'used', 'remaining')->whereId($id_donation)->first();
                    $donation_model->used += $donation;
                    $donation_model->remaining -= $donation;
                    $donation_model->save();

                    DonationHistory::create([
                        'id_donation' => $id_donation,
                        'id_transaction' => $transaction->id,
                        'id_student' => $transaction->id_student,
                        'description' => $donation_descr,
                        'nominal' => $donation,
                        'paid_at' => $transaction->paid_at,
                    ]);
                }

                event(new TransactionBillPaid($transaction));
            });
        }

        if ($error == false) {
            $response = [
                'status' => true,
                'message' => __('message.payment_success', ['label' => __($this->title['payment'])]),
            ];
        } else {
            $response = [
                'status' => false,
                'message' => $error,
            ];
        }

        return response()->json($response);
    }

    public function storeCash(TransactionCashRequest $request)
    {
        $deposit = (object) [];

        DB::transaction(function () use ($request, &$deposit) {
            $trans = $request->transaction;
            $transactions = [];
            $total = 0;

            foreach ($trans as $t) {
                if (empty($t)) {
                    continue;
                }

                $transaction = Transaction::select('id', 'unique_code', 'total', 'status_deposit')->whereId($t)->first();
                $transaction->status_deposit = TransactionDepositStatus::Deposited->value;
                $transaction->save();

                $total += ($transaction->total - $transaction->unique_code);
                array_push($transactions, $t);
            }

            $request->merge([
                'transactions' => $transactions,
                'total' => $total,
            ]);

            $deposit = CashDeposit::create($request->all());
        });

        $response = [
            'status' => true,
            'message' => __('message.create_success', ['label' => __($this->title['cash'])]),
            'data' => [
                'print' => route('finance.transaction.print.cash', $deposit->encrypted_id),
            ],
        ];

        return response()->json($response);
    }

    public function storeUniqueCode(TransactionUniqueCodeRequest $request)
    {
        DB::transaction(function () use ($request) {
            $trans = $request->transaction;
            $transactions = [];
            $total = 0;

            foreach ($trans as $t) {
                if (empty($t)) {
                    continue;
                }

                $transaction = Transaction::select('id', 'unique_code', 'status_deposit_code')->whereId($t)->first();
                $transaction->status_deposit_code = TransactionDepositStatus::Deposited->value;
                $transaction->save();

                $total += $transaction->unique_code;
                array_push($transactions, $t);
            }

            UniqueCodeDeposit::create([
                'dates' => $request->dates,
                'transactions' => $transactions,
                'total' => $total,
            ]);
        });

        $response = [
            'status' => true,
            'message' => __('message.create_success', ['label' => __($this->title['unique-code'])]),
        ];

        return response()->json($response);
    }

    public function storeVerifyCash(TransactionCashVerifyRequest $request, CashDeposit $deposit)
    {
        DB::transaction(function () use ($request, $deposit) {
            $request->merge([
                'verified_at' => date('Y-m-d H:i:s'),
                'verified_by' => Auth::id(),
            ]);

            $deposit->update($request->all());

            if ($deposit->is_rejected) {
                foreach ($deposit->transactions as $t) {
                    $transaction = Transaction::select('id', 'status_deposit')->whereId($t)->first();
                    $transaction->update([
                        'status_deposit' => TransactionDepositStatus::NotDeposit->value,
                    ]);
                }
            }
        });

        return Redirect::route('finance.transaction.cash', 'waiting')->with('success', __('message.verify_success', ['label' => __($this->title['cash'])]));
    }

    public function storeVerifyUniqueCode(TransactionUniqueCodeVerifyRequest $request, UniqueCodeDeposit $deposit)
    {
        DB::transaction(function () use ($request, $deposit) {
            $request->merge([
                'verified_at' => date('Y-m-d H:i:s'),
                'verified_by' => Auth::id(),
            ]);

            $deposit->update($request->all());

            if ($deposit->is_rejected) {
                foreach ($deposit->transactions as $t) {
                    $transaction = Transaction::select('id', 'status_deposit_code')->whereId($t)->first();
                    $transaction->update([
                        'status_deposit_code' => TransactionDepositStatus::NotDeposit->value,
                    ]);
                }
            }
        });

        return Redirect::route('finance.transaction.unique-code', 'waiting')->with('success', __('message.verify_success', ['label' => __($this->title['unique-code'])]));
    }

    public function editCash(CashDeposit $deposit)
    {
        $types = [
            TransactionFlag::Tagihan->value => __('label.bill'),
            TransactionFlag::SetorTabungan->value => __('label.savings_deposit'),
            TransactionFlag::TopupSaldo->value => __('label.topup_balance'),
        ];

        $type_bill = TransactionFlag::Tagihan->value;
        $type_topup = TransactionFlag::TopupSaldo->value;

        return view($this->path['cash'].'edit', [
            'title' => __($this->title_prefix).' - '.__($this->title['cash']),
            'icon' => $this->icon,
            'types' => $types,
            'type_bill' => $type_bill,
            'type_topup' => $type_topup,
            'deposit' => $deposit,
        ]);
    }

    public function editUniqueCode(UniqueCodeDeposit $deposit)
    {
        $types = [
            TransactionFlag::Tagihan->value => __('label.bill'),
            TransactionFlag::SetorTabungan->value => __('label.savings_deposit'),
            TransactionFlag::TopupSaldo->value => __('label.topup_balance'),
        ];

        $type_bill = TransactionFlag::Tagihan->value;
        $type_topup = TransactionFlag::TopupSaldo->value;

        return view($this->path['unique-code'].'edit', [
            'title' => __($this->title_prefix).' - '.__($this->title['unique-code']),
            'icon' => $this->icon,
            'types' => $types,
            'type_bill' => $type_bill,
            'type_topup' => $type_topup,
            'deposit' => $deposit,
        ]);
    }

    public function updateStatus(Request $request)
    {
        $message = '';
        $transaction = Transaction::findOrFail(Crypt::decrypt($request->id));

        DB::transaction(function () use ($request, $transaction, &$message) {
            if ($request->status == 'paid') {
                $update = [
                    'status' => TransactionStatus::Paid->value,
                    'paid_at' => date('Y-m-d H:i:s'),
                    'paid_by' => Auth::id(),
                ];

                if ($request->unique_code == 0) { // Orang tua transfer tanpa kode unik
                    $update['unique_code'] = 0;
                    $update['total'] = $transaction->total - $transaction->unique_code;
                }

                $transaction->update($update);

                if ($transaction->unique_code > 0) {
                    TransactionPaymentCode::whereCode($transaction->unique_code)->delete();
                }

                if ($transaction->is_tagihan) {
                    foreach ($transaction->bills as $b) {
                        $trans_bill = TransactionBill::select('id', 'id_bill', 'id_student', 'total', 'status', 'branch_id')
                            ->with([
                                'bill' => fn ($query) => $query->select('id', 'id_year', 'id_type'),
                                'student' => function ($query) {
                                    $query->select('id', 'id_class')
                                        ->with(['class' => fn ($qc) => $qc->select('id', 'level_education', 'level_class')]);
                                },
                            ])
                            ->whereId($b)
                            ->first();

                        $trans_bill->update([
                            'id_transaction' => $transaction->id,
                            'status' => TransactionStatus::Paid->value,
                        ]);

                        TransactionBill::updateReport($trans_bill, $transaction->paid_at);
                    }

                    event(new TransactionBillPaid($transaction));
                } elseif ($transaction->is_setor_tabungan) {
                    event(new SavingsDepositPaid($transaction));
                } else { // Topup Saldo
                    $parent = Parents::select('id', 'balance')->whereId($transaction->id_parent)->first();
                    $parent->balance += $transaction->subtotal;
                    $parent->save();

                    TopupHistory::create([
                        'id_parent' => $parent->id,
                        'id_transaction' => $transaction->id,
                        'debit' => $transaction->subtotal,
                        'balance' => $parent->balance,
                    ]);
                }

                $message = __('message.payment_success', ['label' => __($this->title_prefix)]);
            } else {
                $transaction->delete();
                $message = __('message.cancel_success', ['label' => __($this->title_prefix)]);
            }
        });

        $response = [
            'status' => true,
            'message' => $message,
        ];

        return response()->json($response);
    }

    public function updateCash(TransactionCashRequest $request, CashDeposit $deposit)
    {
        DB::transaction(function () use ($request, $deposit) {
            $trans = $request->transaction;
            $transactions = [];
            $total = 0;

            foreach ($trans as $t) {
                if (empty($t)) {
                    continue;
                }

                $transaction = Transaction::select('id', 'unique_code', 'total')->whereId($t)->first();
                $transaction->update([
                    'status_deposit' => TransactionDepositStatus::Deposited->value,
                ]);

                $total += ($transaction->total - $transaction->unique_code);
                array_push($transactions, $t);
            }

            foreach ($deposit->transactions as $t) {
                if (empty($t)) {
                    continue;
                }

                if (in_array($t, $transactions)) {
                    continue;
                }

                $transaction = Transaction::select('id', 'status_deposit')->whereId($t)->first();
                $transaction->update([
                    'status_deposit' => TransactionDepositStatus::NotDeposit->value,
                ]);
            }

            $request->merge([
                'transactions' => $transactions,
                'total' => $total,
            ]);

            $deposit->update($request->all());
        });

        $response = [
            'status' => true,
            'message' => __('message.create_success', ['label' => __($this->title['cash'])]),
            'data' => [
                'print' => route('finance.transaction.print.cash', $deposit->encrypted_id),
            ],
        ];

        return response()->json($response);
    }

    public function updateUniqueCode(TransactionUniqueCodeRequest $request, UniqueCodeDeposit $deposit)
    {
        DB::transaction(function () use ($request, $deposit) {
            $trans = $request->transaction;
            $transactions = [];
            $total = 0;

            foreach ($trans as $t) {
                if (empty($t)) {
                    continue;
                }

                $transaction = Transaction::select('id', 'unique_code')->whereId($t)->first();
                $transaction->update([
                    'status_deposit_code' => TransactionDepositStatus::Deposited->value,
                ]);

                $total += $transaction->unique_code;
                array_push($transactions, $t);
            }

            foreach ($deposit->transactions as $t) {
                if (empty($t)) {
                    continue;
                }

                if (in_array($t, $transactions)) {
                    continue;
                }

                $transaction = Transaction::select('id', 'status_deposit_code')->whereId($t)->first();
                $transaction->update([
                    'status_deposit_code' => TransactionDepositStatus::NotDeposit->value,
                ]);
            }

            $deposit->update([
                'dates' => $request->dates,
                'transactions' => $transactions,
                'total' => $total,
            ]);
        });

        $response = [
            'status' => true,
            'message' => __('message.create_success', ['label' => __($this->title['unique-code'])]),
        ];

        return response()->json($response);
    }

    public function destroyCash(CashDeposit $deposit)
    {
        DB::transaction(function () use ($deposit) {
            foreach ($deposit->transactions as $t) {
                $transaction = Transaction::select('id')->whereId($t)->first();
                $transaction->update([
                    'status_deposit' => TransactionDepositStatus::NotDeposit->value,
                ]);
            }

            $deposit->delete();
        });

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title['cash'])]),
        ];

        return response()->json($response);
    }

    public function destroyUniqueCode(UniqueCodeDeposit $deposit)
    {
        DB::transaction(function () use ($deposit) {
            foreach ($deposit->transactions as $t) {
                $transaction = Transaction::select('id')->whereId($t)->first();
                $transaction->update([
                    'status_deposit_code' => TransactionDepositStatus::NotDeposit->value,
                ]);
            }

            $deposit->delete();
        });

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title['unique-code'])]),
        ];

        return response()->json($response);
    }

    public function print(Transaction $transaction)
    {
        $period_monthly = BillPeriod::Monthly->value;
        $period_semester = BillPeriod::Semiannual->value;
        $bills = (object) [];
        $withdrawals = (object) [];

        if ($transaction->is_tagihan) {
            $bills = TransactionBill::select('id', 'id_bill', 'semester', 'months', 'years', 'subtotal', 'discount', 'total')
                ->with([
                    'bill' => function ($query) {
                        $query->select('id', 'id_year', 'id_type', 'name')
                            ->with([
                                'year' => fn ($qy) => $qy->select('id', 'start_year', 'end_year'),
                                'type' => fn ($qt) => $qt->select('id', 'name', 'period'),
                            ]);
                    },
                ])
                ->whereIdTransaction($transaction->id)
                ->orderBy('due_date')
                ->get();
        } elseif ($transaction->is_pengambilan_tabungan) {
            $table_savings = (new SavingsWithdrawal)->getTable();
            $table_student = (new Student)->getTable();
            $table_class = (new Classroom)->getTable();
            $withdrawals = SavingsWithdrawal::select(
                $table_savings.'.id',
                $table_savings.'.number',
                $table_savings.'.dates',
                $table_savings.'.total',
                $table_student.'.nis',
                $table_student.'.name',
                $table_class.'.name AS class_name'
            )
                ->join($table_student, $table_student.'.id', '=', $table_savings.'.id_student')
                ->join($table_class, $table_class.'.id', '=', $table_student.'.id_class')
                ->whereIn($table_savings.'.id', $transaction->bills)
                ->where($table_savings.'.branch_id', Auth::user()->branch_id)
                ->withoutGlobalScope(BranchScope::class)
                ->orderBy($table_class.'.name')
                ->get();
        }

        $pdf = PDF::loadView($this->path['transaction'].'pdf-receipt', [
            'transaction' => $transaction,
            'bills' => $bills,
            'withdrawals' => $withdrawals,
            'withdrawals' => $withdrawals,
            'period' => (object) [
                'monthly' => $period_monthly,
                'semester' => $period_semester,
            ],
        ]);

        // $pdf->setPaper([0, 0, 529, 831], 'landscape');
        $pdf->setPaper([0, 0, 529, 600], 'landscape');

        return $pdf->stream($transaction->number.'-'.date('YmdHis').'.pdf');
    }

    public function printCash(CashDeposit $deposit)
    {
        $time = strtotime($deposit->end_date);

        $pdf = PDF::loadView($this->path['cash'].'pdf', [
            'deposit' => $deposit,
            'month' => date('n', $time),
            'year' => date('Y', $time),
        ]);

        $pdf->setPaper('A4');

        return $pdf->stream($deposit->number.'-'.date('YmdHis').'.pdf');
    }

    public function getBill(Request $request)
    {
        // 1. Perbaikan pencarian NIS + Nama
        $searchData = explode(' - ', $request->search);
        $nisNumber = trim($searchData[0]);

        $queryStudent = Student::select('id')->where('nis', $nisNumber);
        if (isset($searchData[1])) {
            $queryStudent->where('name', trim($searchData[1]));
        }

        $student = $queryStudent->first();
        $student_id = (empty($student)) ? 0 : $student->id;

        $transaction = TransactionBill::select('id', 'id_bill', 'semester', 'months', 'years', 'total', 'due_date')
            ->with(['bill' => fn ($query) => $query->select('id', 'id_type', 'name')->with(['type' => fn ($qt) => $qt->select('id', 'name', 'period')])])
            ->whereIdStudent($student_id)
            ->notPaid()
            ->orderBy('due_date')
            ->get();

        $transactions = [];
        $bills = [];

        if ($transaction->count() > 0) {
            foreach ($transaction as $index => $t) {
                $discount = 0;
                $bill_discount = BillDiscount::select('id', 'id_bill', 'applies_to', 'nominal')
                    ->whereIdStudent($student_id)
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

                // --- INI ADALAH PERUBAHANNYA ---
                // Kita simpan sebagai object/array yang berisi nominal dan id_type
                $bills[$t->id] = [
                    'nominal' => $total,
                    'id_type' => $t->bill->id_type, // Mengambil id_type dari relasi bill
                ];
                // -------------------------------

                array_push($transactions, (object) [
                    'id' => $t->id,
                    'due_date' => $t->due_date,
                    'type' => $t->bill->type->name,
                    'bill' => $t->bill->name,
                    'discount' => $discount,
                    'total' => $total,
                ]);
            }
        }

        $table = view($this->path['bill'].'get-bill', ['transactions' => $transactions])->render();

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'table' => $table,
                'bills' => $bills,
                'student' => $student_id,
            ],
        ];

        return response()->json($response);
    }
}
