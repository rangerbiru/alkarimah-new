<?php

namespace App\Http\Controllers\Finance;

use App\Enums\UserRole;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Allowance;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    private $title_prefix = 'label.payroll';
    private $title = [
        'setup' => 'label.payroll_setup',
        'slip' => 'label.salary_slip',
    ];
    private $icon = [
        'setup' => 'ti ti-device-ipad-dollar',
        'slip' => 'ti ti-receipt-dollar',
    ];
    private $path = [
        'payroll' => 'backend.finance.payroll.',
        'setup' => 'backend.finance.payroll.setup.',
        'slip' => 'backend.finance.payroll.slip.',
    ];

    public function index()
    {
        $payroll = Payroll::select('id', 'months', 'years', 'salary', 'allowance', 'total')
            ->whereIdEmployee(Auth::user()->employee->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view($this->path['payroll'] . 'index', [
            'title' => __($this->title_prefix),
            'icon' => $this->icon['slip'],
            'payroll' => $payroll
        ]);
    }

    public function setup()
    {
        return view($this->path['setup'] . 'index', [
            'title' => __($this->title['setup']),
            'icon' => $this->icon['setup'],
        ]);
    }

    public function slip(Request $request)
    {
        $month = (empty($request->month)) ? date('n') : $request->month;
        $year = (empty($request->year)) ? date('Y') : $request->year;
        $years = Common::option('year');
        $months = Common::option('month');

        return view($this->path['slip'] . 'index', [
            'title' => __($this->title['slip']),
            'icon' => $this->icon['slip'],
            'year' => $year,
            'years' => $years,
            'month' => $month,
            'months' => $months,
        ]);
    }

    public function showSlip(Payroll $payroll)
    {
        $view = (Auth::user()->role == UserRole::Pegawai) ? 'show-pegawai' : 'show';

        return view($this->path['slip'] . $view, [
            'title' => __($this->title['slip']),
            'icon' => $this->icon['slip'],
            'payroll' => $payroll,
        ]);
    }

    public function datatableSetup(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $employee = Employee::select('id', 'nip', 'nik', 'name', 'phone', 'salary', 'salary_allowance')->active();
        $employee_count = $employee->count();

        if (empty($search))
            $employee_filter = $employee;
        else {
            $employee_filter = $employee->where(function ($query) use ($search) {
                $query->where('nip', 'like', '%' . $search . '%')
                    ->orWhere('nik', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . Common::phoneFormat($search) . '%');
            });
        }

        $employee_count_filter = $employee_filter->count();
        $employee_data = $employee_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $employee_arr = [];

        foreach ($employee_data as $e) {
            $push = $e->toArray();
            $push['encrypted_id'] = $e->encrypted_id;
            $push['status'] = (empty($e->salary) && empty($e->salary_allowance)) ? '<span class="badge bg-danger">' . __('label.not_yet') . '</span>' : '<span class="badge bg-success">' . __('label.already') . '</span>';

            array_push($employee_arr, $push);
        }

        $response = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $employee_count,
            'recordsFiltered' => $employee_count_filter,
            'data' => $employee_arr
        ];

        return response()->json($response);
    }

    public function datatableSlip(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $payroll = Payroll::select('id', 'id_employee', 'months', 'years', 'salary', 'allowance', 'total')
            ->with([
                'employee' => fn($query) => $query->select('id', 'nip', 'name')
            ])
            ->whereMonths($request->month)
            ->whereYears($request->year);

        $payroll_count = $payroll->count();

        if (empty($search))
            $payroll_filter = $payroll;
        else {
            $payroll_filter = $payroll->where(function ($query) use ($search) {
                $query->whereHas('employee', function($qe) use($search) {
                    $qe->where('nip', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%');
                });
            });
        }

        $payroll_count_filter = $payroll_filter->count();
        $payroll_data = $payroll_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $payroll_arr = [];

        foreach ($payroll_data as $e) {
            $push = $e->toArray();
            $push['encrypted_id'] = $e->encrypted_id;

            array_push($payroll_arr, $push);
        }

        $response = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $payroll_count,
            'recordsFiltered' => $payroll_count_filter,
            'data' => $payroll_arr
        ];

        return response()->json($response);
    }

    public function editSetup(Employee $employee)
    {
        $allowance_structurals = Allowance::select('id', 'name')->structural()->orderBy('name')->pluck('name', 'id');
        $allowance_liabilities = Allowance::select('id', 'name')->liability()->orderBy('name')->pluck('name', 'id');
        $allowance_performances = Allowance::select('id', 'name')->performance()->orderBy('name')->pluck('name', 'id');

        return view($this->path['setup'] . 'edit', [
            'title' => __($this->title['setup']),
            'icon' => $this->icon['setup'],
            'employee' => $employee,
            'allowances' => (object) [
                'structural' => $allowance_structurals,
                'liability' => $allowance_liabilities,
                'performance' => $allowance_performances,
            ]
        ]);
    }

    public function updateSetup(Request $request, Employee $employee)
    {
        $allowance = ['structural' => [], 'liability' => [], 'performance' => []];
        $allowance_total = 0;

        if (!empty($request->structural)) {
            $nominal = $request->structural_nominal;

            foreach ($request->structural as $index => $s) {
                $n = (empty($nominal[$index])) ? 0 : str_replace('.', '', $nominal[$index]);
                $allowance_total += $n;

                array_push($allowance['structural'], [
                    'id' => $s,
                    'nominal' => $n
                ]);
            }
        }

        if (!empty($request->liability)) {
            $nominal = $request->liability_nominal;

            foreach ($request->liability as $index => $s) {
                $n = (empty($nominal[$index])) ? 0 : str_replace('.', '', $nominal[$index]);
                $allowance_total += $n;

                array_push($allowance['liability'], [
                    'id' => $s,
                    'nominal' => $n
                ]);
            }
        }

        if (!empty($request->performance)) {
            $nominal = $request->performance_nominal;

            foreach ($request->performance as $index => $s) {
                $n = (empty($nominal[$index])) ? 0 : str_replace('.', '', $nominal[$index]);
                $allowance_total += $n;

                array_push($allowance['performance'], [
                    'id' => $s,
                    'nominal' => $n
                ]);
            }
        }

        $employee->update([
            'salary' => str_replace('.', '', $request->salary),
            'salary_allowance' => $allowance_total,
            'salary_allowance_detail' => $allowance
        ]);

        return Redirect::route('finance.payroll.setup')->with('success', __('message.create_success', ['label' => __($this->title['setup'])]));
    }

    public function downloadSlip(Payroll $payroll)
    {
        $pdf = PDF::loadView($this->path['slip'] . 'pdf', [
            'payroll' => $payroll
        ]);

        $pdf->setPaper('A4');

        return $pdf->download(str_replace(' ', '-', strtoupper(__('label.salary_slip'))) . '-' . strtoupper(Common::monthFormat($payroll->months, 'mmm')) . $payroll->years . '-' . date('YmdHis') . '.pdf');
    }
}
