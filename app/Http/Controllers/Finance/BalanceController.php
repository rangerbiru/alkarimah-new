<?php

namespace App\Http\Controllers\Finance;

use App\Enums\PaymentCodeStatus;
use App\Enums\TransactionFlag;
use App\Enums\TransactionMethod;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\TopupHistory;
use App\Models\Transaction;
use App\Models\TransactionPaymentCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class BalanceController extends Controller
{
    private $title = 'label.balance_topup';
    private $icon = 'bx bxs-wallet';
    private $path = 'backend.finance.balance.';

    public function index()
    {
        $payment_code = TransactionPaymentCode::generate(TransactionFlag::TopupSaldo->value);
        $method = (object) [
            'bni' => TransactionMethod::BNI->value,
            'bsi' => TransactionMethod::BSI->value,
        ];

        $waiting = Transaction::select('id', 'number', 'total', 'payment_method')
            ->whereIdParent(Auth::user()->parent->id)
            ->topupSaldo()
            ->notPaid()
            ->first();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'path' => $this->path,
            'method' => $method,
            'payment_code' => $payment_code,
            'waiting' => $waiting,
        ]);
    }

    public function history()
    {
        $status_paid = TransactionStatus::Paid->value;

        return view($this->path . 'history', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'path' => $this->path,
            'status_paid' => $status_paid
        ]);
    }

    public function waiting(Transaction $transaction)
    {
        if ($transaction->is_paid)
            return Redirect::route('finance.balance.index');

        return view('backend.finance.payment.waiting', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'transaction' => $transaction,
            'bills_detail' => [(object) ['name' => __('label.topup_balance_nominal'), 'total' => $transaction->subtotal]]
        ]);
    }

    public function show(TopupHistory $history)
    {
        return view($this->path . 'show', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'history' => $history,
        ]);
    }

    public function store(Request $request)
    {
        $error = false;
        $nominal = (empty($request->nominal)) ? 0 : str_replace('.', '', $request->nominal);

        if ($nominal < 1)
            $error = __('string.balance_more_then_zero');

        if ($error == false) {
            $transaction = (object) [];
            $request->merge([
                'id_parent' => Auth::user()->parent->id,
                'dates' => date('Y-m-d'),
                'flag' => TransactionFlag::TopupSaldo->value,
                'subtotal' => $nominal,
                'total' => $nominal + $request->unique_code,
            ]);

            DB::transaction(function() use($request, &$transaction) {
                $transaction = Transaction::create($request->all());
                TransactionPaymentCode::whereCode($request->unique_code)->update(['status' => PaymentCodeStatus::Used->value]);
            });
        }

        if ($error == false) {
            $response = [
                'status' => true,
                'message' => __('message.process_success', ['label' => __($this->title)]),
                'data' => [
                    'redirect' => route('finance.balance.waiting', $transaction->encrypted_id)
                ]
            ];
        } else {
            $response = [
                'status' => false,
                'message' => $error
            ];
        }

        return response()->json($response);
    }

    public function get()
    {
        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'balance' => Auth::user()->parent->balance
            ]
        ];

        return response()->json($response);
    }

    public function getHistory(Request $request)
    {
        $page = $request->page;
        $limit = 5;
        $offset = ($page - 1) * $limit;
        $parent = Auth::user()->parent->id;
        $transaction = TopupHistory::select('id', 'id_transaction', 'debit', 'credit', 'balance', 'created_at')
            ->with([
                'transaction' => fn($query) => $query->select('id', 'number', 'payment_method', 'paid_at', 'total', 'created_at', 'status')
            ])
            ->whereIdParent($parent);

        if (!empty($request->search)) {
            $search = $request->search;
            $transaction = $transaction->where(function($query) use($search) {
                $query->whereHas('transaction', function($qt) use($search) {
                        $qt->where('number', 'like', '%' . $search . '%')
                            ->orWhere('paid_at', 'like', '%' . $search . '%')
                            ->orWhere('created_at', 'like', '%' . $search . '%');
                    });
            });
        }

        $transaction = $transaction->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $list = view($this->path . 'get-history', [
            'transaction' => $transaction,
        ])->render();

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'count' => $transaction->count(),
                'list' => $list
            ]
        ];

        return response()->json($response);
    }
}
