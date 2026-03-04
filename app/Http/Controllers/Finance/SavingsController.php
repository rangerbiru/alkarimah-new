<?php

namespace App\Http\Controllers\Finance;

use App\Constants\PaymentCodeCookie;
use App\Enums\PaymentCodeStatus;
use App\Enums\SavingsMutationFlag;
use App\Enums\SavingsWithdrawalStatus;
use App\Enums\TransactionFlag;
use App\Enums\TransactionMethod;
use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Events\SavingsDepositPaid;
use App\Events\SavingsWithdrawalProcessed;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Requests\SavingsDepositRequest;
use App\Http\Requests\SavingsWithdrawalProcessRequest;
use App\Http\Requests\SavingsWithdrawalRequest;
use App\Models\Parents;
use App\Models\SavingsMutation;
use App\Models\SavingsWithdrawal;
use App\Models\Setting;
use App\Models\Student;
use App\Models\TopupHistory;
use App\Models\Transaction;
use App\Models\TransactionPaymentCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SavingsController extends Controller
{
    private $title_prefix = 'label.savings';
    private $title = [
        'deposit' => 'label.deposit',
        'withdrawal' => 'label.withdrawal',
        'mutation' => 'label.mutation',
    ];
    private $icon = 'ti ti-cash-banknote-filled';
    private $path = 'backend.finance.savings.';

    public function index() // Role: Orang Tua
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
        $payment_code = TransactionPaymentCode::generate(TransactionFlag::SetorTabungan->value);

        $waiting = Transaction::select('id', 'id_student', 'number', 'total', 'payment_method')
            ->with(['student' => fn($query) => $query->select('id')])
            ->whereHas('student', fn($query) => $query->whereIdParent(Auth::user()->parent->id))
            ->setorTabungan()
            ->notPaid()
            ->first();

        return view($this->path . 'index', [
            'title' => __($this->title_prefix),
            'icon' => $this->icon,
            'path' => $this->path,
            'user' => $user,
            'method' => $method,
            'students' => $students,
            'student_count' => $student_count,
            'student_first' => $student_first,
            'payment_code' => $payment_code,
            'waiting' => $waiting,
        ]);
    }

    public function deposit() // Role: Kasir
    {
        $number = Transaction::generateNumber(TransactionFlag::SetorTabungan->value);

        return view($this->path . 'deposit', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['deposit']),
            'icon' => $this->icon,
            'number' => $number,
        ]);
    }

    public function history(Request $request) // Role: Orang Tua
    {
        if (Auth::user()->role->value == UserRole::Kasir->value)
            return $this->historyCashier($request);
        else
            return $this->historyParent();
    }

    private function historyCashier($request)
    {
        $filter_start = (empty($request->start)) ? date('Y-m') . '-01' : $request->start;
        $filter_end = (empty($request->end)) ? date('Y-m-t') : $request->end;

        return view($this->path . 'history-cashier', [
            'title' => __($this->title_prefix) . ' - ' . __('label.deposit_history'),
            'icon' => $this->icon,
            'filter' => (object) [
                'start' => $filter_start,
                'end' => $filter_end,
            ]
        ]);
    }

    private function historyParent()
    {
        $status_paid = TransactionStatus::Paid->value;

        return view($this->path . 'history-parent', [
            'title' => __($this->title_prefix),
            'icon' => $this->icon,
            'path' => $this->path,
            'status_paid' => $status_paid
        ]);
    }

    public function historyWithdrawal(Request $request)
    {
        $filter_start = (empty($request->start)) ? date('Y-m') . '-01' : $request->start;
        $filter_end = (empty($request->end)) ? date('Y-m-t') : $request->end;

        return view($this->path . 'history-withdrawal', [
            'title' => __($this->title_prefix),
            'icon' => $this->icon,
            'filter' => (object) [
                'start' => $filter_start,
                'end' => $filter_end,
            ]
        ]);
    }

    public function waiting(Transaction $transaction)
    {
        if ($transaction->is_paid)
            return Redirect::route('finance.savings.index');

        return view('backend.finance.payment.waiting', [
            'title' => __($this->title_prefix),
            'icon' => $this->icon,
            'transaction' => $transaction,
            'bills_detail' => [(object) ['name' => __('label.savings_deposit_nominal'), 'total' => $transaction->subtotal]]
        ]);
    }

    public function withdrawal()
    {
        $number = Transaction::generateNumber(TransactionFlag::PengambilanTabungan->value);
        $persons = User::select('id', 'name')->penanggungJawabTabungan()->orderBy('name')->pluck('name', 'id');

        return view($this->path . 'withdrawal', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['withdrawal']),
            'icon' => $this->icon,
            'number' => $number,
            'persons' => $persons,
        ]);
    }

    public function show(Transaction $transaction)
    {
        return view($this->path . 'show', [
            'title' => __($this->title_prefix),
            'icon' => $this->icon,
            'transaction' => $transaction,
        ]);
    }

    public function showWithdrawal(SavingsWithdrawal $withdrawal)
    {
        return view($this->path . 'show-withdrawal', [
            'title' => __($this->title_prefix),
            'icon' => $this->icon,
            'withdrawal' => $withdrawal,
        ]);
    }

    public function mutation()
    {
        return view($this->path . 'mutation', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['mutation']),
            'icon' => $this->icon,
        ]);
    }

    public function datatableHistory(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $transaction = Transaction::select('id', 'id_student', 'number', 'dates', 'total', 'payment_method', 'paid_at')
            ->with([
                'student' => function($query) {
                    $query->select('id', 'id_class', 'nis', 'name', 'balance_savings')
                        ->with(['class' => fn($qc) => $qc->select('id', 'name', 'level_education')]);
                },
            ])
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date == $end_date)
                    $query->whereDates($start_date);
                else
                    $query->whereRaw('DATE(dates) BETWEEN (? AND ?)', [$start_date, $end_date]);
            })
            ->paid()
            ->setorTabungan();

        $transaction_count = $transaction->count();

        if (empty($search))
            $transaction_filter = $transaction;
        else {
            $transaction_filter = $transaction->where(function ($query) use ($search) {
                $query->where('number', 'like', '%' . $search . '%')
                    ->orWhere('dates', 'like', '%' . $search . '%')
                    ->orWhereHas('student', function($qs) use($search) {
                        $qs->where('nis', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%')
                            ->orWhereHas('class', function($qc) use($search) {
                                $qc->where('name', 'like', '%' . $search . '%')
                                    ->orWhere('level_education', 'like', '%' . $search . '%');
                            });
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
            $push['level_education'] = strtoupper($t->student->class->level_education->value);

            array_push($transaction_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $transaction_count,
            'recordsFiltered' => $transaction_count_filter,
            'data' => $transaction_arr
        ]);
    }

    public function datatableHistoryWithdrawal(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $withdrawal = SavingsWithdrawal::select('id', 'id_student', 'number', 'dates', 'total', 'processed_at')
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'id_class', 'nis', 'name')
                        ->with(['class' => fn($qc) => $qc->select('id', 'name', 'level_education')]);
                },
            ])
            ->where(function ($query) use ($request) {
                $query->whereBetween('dates', [$request->start_date, $request->end_date]);
            })
            ->processed();

        $withdrawal_count = $withdrawal->count();

        if (empty($search))
            $withdrawal_filter = $withdrawal;
        else {
            $withdrawal_filter = $withdrawal->where(function ($query) use ($search) {
                $query->where('number', 'like', '%' . $search . '%')
                    ->orWhere('dates', 'like', '%' . $search . '%')
                    ->orWhereHas('student', function ($qs) use ($search) {
                        $qs->where('nis', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%')
                            ->orWhereHas('class', function ($qc) use ($search) {
                                $qc->where('name', 'like', '%' . $search . '%')
                                    ->orWhere('level_education', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        $withdrawal_count_filter = $withdrawal_filter->count();
        $withdrawal_data = $withdrawal_filter->limit($limit)
            ->offset($start)
            ->orderBy('processed_at', 'desc')
            ->get();

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $withdrawal_count,
            'recordsFiltered' => $withdrawal_count_filter,
            'data' => $withdrawal_data
        ]);
    }

    public function datatableMutation(Request $request)
    {
        $st = explode(' - ', $request->student);
        $student = Student::select('id')->whereNis(trim($st[0]))->first();
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $mutation = SavingsMutation::select('id', 'debit', 'credit', 'balance', 'flag', 'created_at')
            ->whereIdStudent($student->id);

        if (!empty($request->start_date)) {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $mutation = $mutation->where(function ($query) use ($start_date, $end_date) {
                if ($start_date == $end_date)
                    $query->whereDate('created_at', $start_date);
                else
                    $query->whereRaw('DATE(created_at) BETWEEN ? AND ?', [$start_date, $end_date]);
            });
        }

        $mutation_count = $mutation->count();

        if (empty($search))
            $mutation_filter = $mutation;
        else {
            $mutation_filter = $mutation->where(function ($query) use ($search) {
                $query->where('created_at', 'like', '%' . $search . '%');

                if (stristr($search, strtolower(__('label.deposit'))))
                    $query->orWhere('flag', SavingsMutationFlag::Deposit->value);
                else if (stristr($search, strtolower(__('label.withdrawal'))))
                    $query->orWhere('flag', SavingsMutationFlag::Withdrawal->value);
            });
        }

        $mutation_count_filter = $mutation_filter->count();
        $mutation_data = $mutation_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $mutation_arr = [];

        foreach ($mutation_data as $t) {
            $push = $t->toArray();
            $push['flag_name'] = $t->flag_name;

            array_push($mutation_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $mutation_count,
            'recordsFiltered' => $mutation_count_filter,
            'data' => $mutation_arr
        ]);
    }

    public function createWithdrawal()
    {
        $number = SavingsWithdrawal::generateNumber();

        return view($this->path . 'create-withdrawal', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['withdrawal']),
            'icon' => $this->icon,
            'number' => $number
        ]);
    }

    public function store(SavingsDepositRequest $request)
    {
        if (Auth::user()->role->value == UserRole::Kasir->value)
            return $this->storeCashier($request);
        else // Orang Tua
            return $this->storeParent($request);
    }

    private function storeCashier($request)
    {
        DB::transaction(function() use($request) {
            $request->merge([
                'subtotal' => $request->nominal,
                'total' => $request->nominal,
                'payment_method' => TransactionMethod::Cash->value,
                'paid_at' => date('Y-m-d H:i:s'),
                'paid_by' => Auth::id(),
                'status' => TransactionStatus::Paid->value,
                'flag' => TransactionFlag::SetorTabungan->value,
            ]);

            $transaction = Transaction::create($request->all());

            event(new SavingsDepositPaid($transaction));
        });

        $student = Student::select('balance_savings')->whereId($request->id_student)->first();

        $response = [
            'status' => true,
            'message' => __('message.process_success', ['label' => __('label.savings_deposit')]),
            'data' => [
                'balance' => $student->balance_savings
            ]
        ];

        return response()->json($response);
    }

    private function storeParent($request)
    {
        $error = false;
        $nominal = (empty($request->nominal)) ? 0 : str_replace('.', '', $request->nominal);

        if ($nominal < 1)
            $error = __('string.balance_more_then_zero');

        if ($error == false) {
            $transaction = (object) [];

            DB::transaction(function() use($request, $nominal, &$transaction) {
                $merge = [
                    'dates' => date('Y-m-d'),
                    'subtotal' => $nominal,
                    'total' => $nominal + $request->unique_code,
                    'flag' => TransactionFlag::SetorTabungan->value,
                ];

                TransactionPaymentCode::whereCode($request->unique_code)->update(['status' => PaymentCodeStatus::Used->value]);

                $request->merge($merge);
                $transaction = Transaction::create($request->all());
            });
        }

        if ($error == false) {
            $response = [
                'status' => true,
                'message' => __('message.process_success', ['label' => __('label.savings_deposit')]),
                'data' => [
                    'redirect' => route('finance.savings.waiting', $transaction->encrypted_id)
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

    public function storeWithdrawal(SavingsWithdrawalRequest $request)
    {
        DB::transaction(function() use($request) {
            $withdrawal = SavingsWithdrawal::create($request->all());
            Student::whereId($withdrawal->id_student)->decrement('balance_savings', $withdrawal->total);
        });

        $response = [
            'status' => true,
            'message' => __('message.create_success', ['label' => __('label.savings_withdrawal')]),
            'data' => [
                'total' => $request->total
            ]
        ];

        return response()->json($response);
    }

    public function editWithdrawal(SavingsWithdrawal $withdrawal)
    {
        return view($this->path . 'edit-withdrawal', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['withdrawal']),
            'icon' => $this->icon,
            'withdrawal' => $withdrawal
        ]);
    }

    public function updateWithdrawal(SavingsWithdrawalRequest $request, SavingsWithdrawal $withdrawal)
    {
        DB::transaction(function () use ($request, $withdrawal) {
            $total_before = $withdrawal->total;
            $withdrawal->update($request->all());
            $total = $withdrawal->total - $total_before;

            Student::whereId($withdrawal->id_student)->decrement('balance_savings', $total);
        });

        $response = [
            'status' => true,
            'message' => __('message.update_success', ['label' => __('label.savings_withdrawal')]),
        ];

        return response()->json($response);
    }

    public function destroyWithdrawal(SavingsWithdrawal $withdrawal)
    {
        DB::transaction(function () use ($withdrawal) {
            Student::whereId($withdrawal->id_student)->increment('balance_savings', $withdrawal->total);
            $withdrawal->delete();
        });

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __('label.savings_withdrawal')])
        ];

        return response()->json($response);
    }

    public function processWithdrawal(SavingsWithdrawalProcessRequest $request)
    {
        $transaction = (object) [];

        DB::transaction(function() use($request, &$transaction) {
            $bills = $request->bills;

            $request->merge([
                'bills' => array_keys($bills),
                'subtotal' => $request->total,
                'payment_method' => TransactionMethod::Cash->value,
                'status' => TransactionStatus::Paid->value,
                'paid_at' => date('Y-m-d H:i:s'),
                'paid_by' => Auth::id(),
                'flag' => TransactionFlag::PengambilanTabungan->value
            ]);

            $transaction = Transaction::create($request->all());

            foreach ($bills as $id => $b)
                event(new SavingsWithdrawalProcessed($transaction, $id));
        });

        $response = [
            'status' => true,
            'message' => __('message.process_success', ['label' => __('label.savings_withdrawal')]),
            'data' => [
                'print' => route('finance.savings.download.excel.withdrawal', $transaction->encrypted_id)
            ]
        ];

        return response()->json($response);
    }

    public function downloadExcelWithdrawal(Transaction $transaction) {
        $setting = Setting::select('savings_withdrawal_limit', 'savings_withdrawal_limit_max')->first();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $style_col = [
            'font' => ['bold' => true], // Set font nya jadi bold
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Set text jadi ditengah secara horizontal (center)
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER // Set text jadi di tengah secara vertical (middle)
            ],
            'borders' => [
                'top' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN], // Set border top dengan garis tipis
                'right' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],  // Set border right dengan garis tipis
                'bottom' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN], // Set border bottom dengan garis tipis
                'left' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN] // Set border left dengan garis tipis
            ]
        ];

        $style_row = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER // Set text jadi di tengah secara vertical (middle)
            ],
            'borders' => [
                'top' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN], // Set border top dengan garis tipis
                'right' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],  // Set border right dengan garis tipis
                'bottom' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN], // Set border bottom dengan garis tipis
                'left' => ['borderStyle'  => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN] // Set border left dengan garis tipis
            ]
        ];

        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        $last_col = end($cols);
        $row = 1;

        $sheet->setCellValue('A' . $row, __('label.proof_savings_withdrawal'));
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, __('label.transaction_number') . ' : ' . $transaction->number);
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $row++;

        $sheet->setCellValue('A' . $row, __('label.transaction_date') . ' : ' . Common::dateFormat($transaction->created_at));
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $row += 2;

        if ($setting->savings_withdrawal_limit) {
            $sheet->setCellValue('A' . $row, '1. Penarikan Maksimal Rp. ' . number_format($setting->savings_withdrawal_limit_max, 0, '', '.') . ' / Pekan');
            $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
            $row++;

            $sheet->setCellValue('A' . $row, '2. Penarikan lebih dari Rp. ' . number_format($setting->savings_withdrawal_limit_max, 0, '', '.') . ' dipekan terakhir setiap bulan');
            $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
            $row++;

            $sheet->setCellValue('A' . $row, '3. Santri dilarang menarik langsung ke kasir, wajib melalui mekanisme yang telah diatur');
            $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
            $row++;

            $sheet->setCellValue('A' . $row, '4. Pengajuan wajib sesuai waktu yang telah disepakati');
            $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
            $row += 2;
        } else {
            $sheet->setCellValue('A' . $row, '1. Santri dilarang menarik langsung ke kasir, wajib melalui mekanisme yang telah diatur');
            $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
            $row++;

            $sheet->setCellValue('A' . $row, '2. Pengajuan wajib sesuai waktu yang telah disepakati');
            $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
            $row += 2;
        }

        $withdrawals = [];
        $withdrawal = SavingsWithdrawal::select('id', 'id_student', 'total')
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'id_class', 'nis', 'name')
                        ->with([
                            'class' => function ($qc) {
                                $qc->select('id', 'id_wali_kelas', 'name')
                                    ->with(['waliKelas' => fn($qw) => $qw->select('id', 'name')]);
                            }
                        ]);
                }
            ])
            ->whereIn('id', $transaction->bills)
            ->orderBy('created_at')
            ->get();

        foreach ($withdrawal as $w) {
            if (!array_key_exists($w->student->id_class, $withdrawals)) {
                $withdrawals[$w->student->id_class] = [
                    'name' => $w->student->class->name,
                    'wali_kelas' => $w->student->class->waliKelas->name,
                    'data' => []
                ];
            }

            array_push($withdrawals[$w->student->id_class]['data'], [
                'nis' => $w->student->nis,
                'name' => $w->student->name,
                'total' => $w->total,
            ]);
        }

        foreach ($withdrawals as $w) {
            // Table Header
            $sheet->setCellValue('A' . $row, $w['name']);
            $sheet->setCellValue('D' . $row, __('label.amount'));

            foreach ($cols as $c)
                $sheet->getStyle($c . $row)->applyFromArray($style_col);

            $row_merge = $row + 1;
            $sheet->mergeCells('A' . $row . ':C' . $row);
            $sheet->mergeCells('D' . $row . ':' . $last_col . $row_merge);

            $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle('A' . $row)->getFill()->getStartColor()->setARGB('F9C023');
            $row++;

            $sheet->setCellValue('A' . $row, $w['wali_kelas']);

            foreach ($cols as $c)
                $sheet->getStyle($c . $row)->applyFromArray($style_col);

            $sheet->mergeCells('A' . $row . ':C' . $row);
            $row++;

            $sheet->setCellValue('A' . $row, __('label.no'));
            $sheet->setCellValue('B' . $row, __('label.nis'));
            $sheet->setCellValue('C' . $row, __('label.name'));
            $sheet->setCellValue('D' . $row, __('label.submitted'));
            $sheet->setCellValue('E' . $row, __('label.approved'));
            $sheet->setCellValue('F' . $row, __('label.signature'));

            foreach ($cols as $c)
                $sheet->getStyle($c . $row)->applyFromArray($style_col);

            $row++;

            // Table Body
            $no = 1;

            foreach ($w['data'] as $d) {
                $sheet->setCellValue('A' . $row, $no);
                $sheet->setCellValueExplicit('B' . $row, $d['nis'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('C' . $row, $d['name']);
                $sheet->setCellValue('D' . $row, $d['total']);
                $sheet->setCellValue('E' . $row, $d['total']);

                foreach ($cols as $c)
                    $sheet->getStyle($c . $row)->applyFromArray($style_row);

                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                $no++;
                $row++;
            }

            $row++;
        }

        // Setting
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(25);

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->setTitle(__('label.proof_savings_withdrawal'));

        // Output
        $filename = str_replace(' ', '-', strtolower(__('label.proof_savings_withdrawal'))) . '-' . date('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    public function get(Request $request) // Role: Orang Tua
    {
        $student = Student::select('balance_savings')->whereId($request->student)->first();

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'balance' => $student->balance_savings
            ]
        ];

        return response()->json($response);
    }

    public function getStudent(Request $request) // Role: Kasir
    {
        $nis = explode(' - ', $request->nis);
        $student = Student::select('id', 'id_parent', 'id_class', 'nis', 'name', 'balance_savings')
            ->with([
                'parent' => fn($query) => $query->select('id', 'name', 'phone'),
                'class' => fn($query) => $query->select('id', 'name', 'level_education'),
            ])
            ->whereNis($nis[0])
            ->firstOrFail();

        $view = view($this->path . 'get-student', ['student' => $student])->render();

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'id' => @$student->id,
                'balance' => (empty($student)) ? 0 : $student->balance_savings,
                'student' => $view
            ]
        ];

        return response()->json($response);
    }

    public function getWithdrawal(Request $request)
    {
        $withdrawal = SavingsWithdrawal::select('id', 'id_student', 'number', 'dates', 'total')
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'id_class', 'nis', 'name')
                        ->with(['class' => fn($qc) => $qc->select('id', 'name', 'level_education')]);
                }
            ])
            ->whereCreatedBy($request->person)
            ->notProcessed()
            ->orderBy('created_at')
            ->get();

        $view = view($this->path . 'get-withdrawal', ['withdrawal' => $withdrawal])->render();

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'table' => $view,
                'withdrawals' => $withdrawal->pluck('total', 'id')
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

        $mutation = SavingsMutation::select('id', 'id_transaction', 'flag')
            ->with([
                'student' => fn($query) => $query->select('id'),
                'transaction' => fn($query) => $query->select('id', 'number', 'payment_method', 'paid_at', 'total', 'created_at', 'status'),
                'withdrawal' => function($query) {
                    $query->select('id', 'number', 'total', 'status', 'processed_at', 'created_at', 'created_by')
                        ->with(['creator' => fn($qc) => $qc->select('id', 'name')]);
                }
            ])
            ->whereHas('student', function($query) use($parent) {
                $query->whereIdParent($parent);
            });

        if (!empty($request->search)) {
            $search = $request->search;
            $mutation = $mutation->where(function($query) use($search) {
                $query->whereHas('transaction', function($qt) use($search) {
                    $qt->where('number', 'like', '%' . $search . '%')
                        ->orWhere('paid_at', 'like', '%' . $search . '%')
                        ->orWhere('created_at', 'like', '%' . $search . '%');
                })
                ->orWhereHas('withdrawal', function($qw) use($search) {
                    $qw->where('number', 'like', '%' . $search . '%')
                        ->orWhere('processed_at', 'like', '%' . $search . '%')
                        ->orWhere('created_at', 'like', '%' . $search . '%');
                });
            });
        }

        $mutation = $mutation->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $list = view($this->path . 'get-history-parent', [
            'mutation' => $mutation,
            'flag_withdrawal' => SavingsMutationFlag::Withdrawal
        ])->render();

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'count' => $mutation->count(),
                'list' => $list
            ]
        ];

        return response()->json($response);
    }
}
