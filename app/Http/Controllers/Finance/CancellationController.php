<?php

namespace App\Http\Controllers\Finance;

use App\Enums\TransactionFlag;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\CancellationException;
use App\Services\TransactionCancellation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CancellationController extends Controller
{
    private $title = 'label.transaction_cancellation';

    private $icon = 'bx bx-revision';

    private $path = 'backend.finance.cancellation.';

    public function __construct(private TransactionCancellation $service) {}

    public function index(Request $request)
    {
        $types = [
            TransactionFlag::Tagihan->value => __('label.bill'),
            TransactionFlag::SetorTabungan->value => __('label.savings_deposit'),
            TransactionFlag::TopupSaldo->value => __('label.topup_balance'),
        ];

        return view($this->path.'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'types' => $types,
            'reason' => config('transaction-cancellation.reason'),
            'filter' => $this->period($request),
        ]);
    }

    public function history(Request $request)
    {
        return view($this->path.'history', [
            'title' => __($this->title).' - '.__('label.cancellation_history'),
            'icon' => $this->icon,
            'filter' => $this->period($request),
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? null;
        $limit = max(1, min(100, (int) $request->input('length', 10)));
        $start = max(0, (int) $request->input('start', 0));
        [$from, $to] = $this->dates($request->input('start_date'), $request->input('end_date'));

        $scope = config('transaction-cancellation.scope');
        $type = (string) $request->input('type');

        $query = Transaction::select('id', 'id_parent', 'id_student', 'number', 'subtotal', 'donation', 'total',
            'payment_method', 'paid_at', 'flag', 'unique_code', 'status')
            ->with([
                'student' => fn ($q) => $q->select('id', 'nis', 'name', 'id_parent'),
                'parent' => fn ($q) => $q->select('id', 'name', 'phone'),
            ])
            ->whereIn('status', [TransactionStatus::Paid->value, TransactionStatus::Cancelled->value])
            ->whereRaw('DATE(paid_at) BETWEEN ? AND ?', [$from, $to])
            ->where(function ($q) use ($scope) {
                foreach ($scope as $flag => $methods) {
                    $q->orWhere(fn ($w) => $w->where('flag', $flag)->whereIn('payment_method', $methods));
                }
            });

        if ($type !== '' && isset($scope[$type])) {
            $query->where('flag', $type);
        }

        $total = (clone $query)->count();

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('number', 'like', $like)
                    ->orWhereHas('student', function ($s) use ($like) {
                        $s->where('nis', 'like', $like)
                            ->orWhere('name', 'like', $like)
                            ->orWhereHas('parent', fn ($p) => $p->where('name', 'like', $like));
                    })
                    ->orWhereHas('parent', fn ($p) => $p->where('name', 'like', $like));
            });
        }

        $filtered = (clone $query)->count();
        $rows = $query->orderByDesc('paid_at')->orderByDesc('id')->limit($limit)->offset($start)->get();

        // Ortu transaksi tagihan / setor tabungan dicari lewat santri
        $parents = DB::table('parent')->whereIn('id', $rows->map(fn ($t) => $t->parent?->id ?? $t->student?->id_parent)->filter()->unique())
            ->get(['id', 'name', 'phone'])->keyBy('id');

        $data = $rows->map(function ($t) use ($parents) {
            $parent = $t->parent ?? $parents->get($t->student?->id_parent);

            return [
                'encrypted_id' => $t->encrypted_id,
                'number' => $t->number,
                'type' => (string) $t->flag->value,
                'type_name' => $t->flag_detail->name ?? '',
                'student' => $t->student ? ['nis' => $t->student->nis, 'name' => $t->student->name] : null,
                'parent' => $parent ? ['name' => $parent->name, 'phone' => $parent->phone] : null,
                'method' => $t->method->name,
                'paid_at' => $t->paid_at,
                'total' => (float) $t->total,
                'cancelled' => $t->status === TransactionStatus::Cancelled,
            ];
        })->values();

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    public function datatableHistory(Request $request)
    {
        $search = $request->input('search')['value'] ?? null;
        $limit = max(1, min(100, (int) $request->input('length', 10)));
        $start = max(0, (int) $request->input('start', 0));
        [$from, $to] = $this->dates($request->input('start_date'), $request->input('end_date'));

        $query = DB::table('transaction_cancellation as c')
            ->join('transaction as t', 't.id', '=', 'c.id_transaction')
            ->leftJoin('student as s', 's.id', '=', 't.id_student')
            ->leftJoin('parent as p', 'p.id', '=', DB::raw('COALESCE(t.id_parent, s.id_parent)'))
            ->leftJoin('users as u', 'u.id', '=', 'c.cancelled_by')
            ->where('c.branch_id', Auth::user()->branch_id)
            ->whereRaw('DATE(c.cancelled_at) BETWEEN ? AND ?', [$from, $to]);

        $total = (clone $query)->count();

        if (! empty($search)) {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('t.number', 'like', $like)
                    ->orWhere('s.nis', 'like', $like)
                    ->orWhere('s.name', 'like', $like)
                    ->orWhere('p.name', 'like', $like)
                    ->orWhere('c.reason', 'like', $like)
                    ->orWhere('u.name', 'like', $like);
            });
        }

        $filtered = (clone $query)->count();
        $rows = $query->orderByDesc('c.cancelled_at')->orderByDesc('c.id')->limit($limit)->offset($start)
            ->get(['c.id', 'c.reason', 'c.cancelled_at', 't.number', 't.flag', 't.paid_at', 't.total',
                's.nis as student_nis', 's.name as student_name', 'p.name as parent_name', 'u.name as cancelled_by_name']);

        $labels = [
            TransactionFlag::Tagihan->value => __('label.bill'),
            TransactionFlag::SetorTabungan->value => __('label.savings_deposit'),
            TransactionFlag::TopupSaldo->value => __('label.topup_balance'),
        ];

        $data = $rows->map(fn ($r) => [
            'number' => $r->number,
            'type_name' => $labels[$r->flag] ?? '',
            'student' => $r->student_name ? ['nis' => $r->student_nis, 'name' => $r->student_name] : null,
            'parent_name' => $r->parent_name,
            'paid_at' => $r->paid_at,
            'total' => (float) $r->total,
            'reason' => $r->reason,
            'cancelled_by' => $r->cancelled_by_name,
            'cancelled_at' => $r->cancelled_at,
        ])->values();

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    public function preview(Transaction $transaction)
    {
        try {
            return response()->json(['status' => true, 'data' => $this->service->preview($transaction->id)]);
        } catch (CancellationException $e) {
            return $this->rejected($e);
        } catch (Throwable $e) {
            return $this->failed($e);
        }
    }

    public function store(Request $request, Transaction $transaction)
    {
        try {
            $result = $this->service->cancel($transaction->id, (string) $request->input('reason'));
        } catch (CancellationException $e) {
            return $this->rejected($e);
        } catch (Throwable $e) {
            return $this->failed($e);
        }

        return response()->json([
            'status' => true,
            'message' => __('message.cancel_transaction_success', ['number' => $result['number']]),
            'data' => $result,
        ]);
    }

    private function rejected(CancellationException $e)
    {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
            'blockers' => $e->blockers,
        ], in_array('forbidden', $e->codes(), true) ? 403 : 422);
    }

    private function failed(Throwable $e)
    {
        $reference = 'CNL-'.date('ymdHis').'-'.Str::upper(Str::random(4));
        Log::error($reference.' '.$e->getMessage(), ['exception' => $e]);

        return response()->json([
            'status' => false,
            'message' => __('message.server_error_reference', ['reference' => $reference]),
        ], 500);
    }

    /** @return object{start: string, end: string} */
    private function period(Request $request): object
    {
        [$from, $to] = $this->dates($request->query('start'), $request->query('end'));

        return (object) ['start' => $from, 'end' => $to];
    }

    /** Hanya tanggal YYYY-MM-DD yang valid; selain itu memakai bulan ini. */
    private function dates($from, $to): array
    {
        $valid = fn ($d) => is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && checkdate((int) substr($d, 5, 2), (int) substr($d, 8, 2), (int) substr($d, 0, 4));

        return [
            $valid($from) ? $from : date('Y-m').'-01',
            $valid($to) ? $to : date('Y-m-t'),
        ];
    }
}
