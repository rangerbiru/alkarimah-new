<?php

namespace App\Http\Controllers\Finance;

use App\Enums\BillPeriod;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Requests\BillDiscountRequest;
use App\Http\Requests\BillGenerateRequest;
use App\Http\Requests\BillSetupRequest;
use App\Http\Requests\BillTypeRequest;
use App\Models\Bill;
use App\Models\BillDiscount;
use App\Models\BillType;
use App\Models\Classroom;
use App\Models\ReportStudent;
use App\Models\Scopes\ActiveScope;
use App\Models\Student;
use App\Models\TransactionBill;
use App\Models\Year;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class BillController extends Controller
{
    private $title_prefix = 'label.bill';
    private $title = [
        'type' => 'label.type',
        'setup' => 'label.setup',
        'discount' => 'label.discount',
    ];
    private $icon = 'bx bx-credit-card-front';
    private $path = [
        'type' => 'backend.finance.bill.type.',
        'setup' => 'backend.finance.bill.setup.',
        'discount' => 'backend.finance.bill.discount.'
    ];

    public function index()
    {
        $classes = Classroom::select('id', 'name')->orderBy('name')->pluck('name', 'id');
        $years = Year::selectRaw('id, CONCAT("Thn. Ajaran : ", start_year, " - ", end_year) AS start_year')->orderBy('start_year', 'desc')->pluck('start_year', 'id');
        $year = Year::select('id')->active()->first();

        return view($this->path['setup'] . 'list', [
            'title' => __($this->title_prefix),
            'icon' => $this->icon,
            'path' => $this->path['setup'],
            'classes' => $classes,
            'years' => $years,
            'year' => $year,
        ]);
    }

    public function type()
    {
        $count = BillType::count();
        $period_monthly = BillPeriod::Monthly->value;

        return view($this->path['type'] . 'index', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['type']),
            'icon' => $this->icon,
            'count' => $count,
            'period_monthly' => $period_monthly,
        ]);
    }

    public function setup()
    {
        $years = Year::selectRaw('id, CONCAT("Thn. Ajaran : ", start_year, " - ", end_year) AS start_year')->orderBy('start_year', 'desc')->pluck('start_year', 'id');
        $year = Year::select('id')->active()->first();
        $period_onetime = BillPeriod::OneTime->value;

        return view($this->path['setup'] . 'index', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['setup']),
            'path' => $this->path['setup'],
            'icon' => $this->icon,
            'years' => $years,
            'year' => $year,
            'period_onetime' => $period_onetime,
        ]);
    }

    public function setting()
    {
        $educations = Common::option('education_level');
        $years = Year::selectRaw('id, CONCAT("Thn. Ajaran : ", start_year, " - ", end_year) AS start_year')->orderBy('start_year', 'desc')->pluck('start_year', 'id');
        $year = Year::select('id')->active()->first();

        return view($this->path['setup'] . 'setting', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['setup']),
            'path' => $this->path['setup'],
            'icon' => $this->icon,
            'educations' => $educations,
            'years' => $years,
            'year' => $year,
        ]);
    }

    public function discount()
    {
        $count = BillDiscount::count();

        return view($this->path['discount'] . 'index', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['discount']),
            'path' => $this->path['discount'],
            'icon' => $this->icon,
            'count' => $count
        ]);
    }

    public function datatableType(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $bill = BillType::select('id', 'name', 'period');

        $bill_count = $bill->count();

        if (empty($search))
            $bill_filter = $bill;
        else {
            $bill_filter = $bill->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        $bill_count_filter = $bill_filter->count();
        $bill_data = $bill_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $bill_arr = [];

        foreach ($bill_data as $b) {
            $push = $b->toArray();
            $push['encrypted_id'] = $b->encrypted_id;
            $push['period_name'] = $b->period_name;

            array_push($bill_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $bill_count,
            'recordsFiltered' => $bill_count_filter,
            'data' => $bill_arr
        ]);
    }

    public function datatableSetup(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $bill = Bill::select('id', 'id_type', 'name', 'nominal', 'billing_date', 'due_date', 'description', 'start_month', 'start_year',
                'end_month', 'end_year')
            ->with(['type' => fn($query) => $query->select('id', 'name', 'period')])
            ->whereIdYear($request->year);

        $bill_count = $bill->count();

        if (empty($search))
            $bill_filter = $bill;
        else {
            $bill_filter = $bill->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('billing_date', 'like', '%' . $search . '%')
                    ->orWhere('due_date', 'like', '%' . $search . '%')
                    ->orWhereHas('type', function($qt) use($search) {
                        $qt->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $bill_count_filter = $bill_filter->count();
        $bill_data = $bill_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $bill_arr = [];

        foreach ($bill_data as $b) {
            $push = $b->toArray();
            $push['encrypted_id'] = $b->encrypted_id;
            $push['billing_date_day'] = $b->billing_date_day;
            $push['due_date_day'] = $b->due_date_day;
            $push['period'] = $b->type->period;
            $push['period_name'] = $b->type->period_name;

            if ($b->type->is_period_monthly) {
                $push['period_monthly'] = true;

                if ($b->start_year == $b->end_year)
                    $push['period'] = Common::monthFormat($b->start_month, 'mmm') . ' - ' . Common::monthFormat($b->end_month, 'mmm') . ' ' . $b->start_year;
                else
                    $push['period'] = Common::monthFormat($b->start_month, 'mmm') . ' ' . $b->start_year . ' - ' . Common::monthFormat($b->end_month, 'mmm') . ' ' . $b->end_year;
            }

            array_push($bill_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $bill_count,
            'recordsFiltered' => $bill_count_filter,
            'data' => $bill_arr
        ]);
    }

    public function datatableStudent(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $student = Student::select('id', 'nis', 'name')->whereIdClass($request->class);
        $student_count = $student->count();

        if (empty($search))
            $student_filter = $student;
        else {
            $student_filter = $student->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('nis', 'like', '%' . $search . '%');
            });
        }

        $student_count_filter = $student_filter->count();
        $student_data = $student_filter->limit($limit)
            ->offset($start)
            ->orderBy('name')
            ->get();

        $response = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $student_count,
            'recordsFiltered' => $student_count_filter,
            'data' => $student_data
        ];

        return response()->json($response);
    }

    public function datatableList(Request $request)
    {
        $year = $request->year;
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $student = Student::select('id', 'id_class', 'nis', 'name', 'bills')
            ->with(['class' => fn($query) => $query->select('id', 'name')]);

        if (!empty($request->class))
            $student = $student->whereIdClass($request->class);

        $student_count = $student->count();

        if (empty($search))
            $student_filter = $student;
        else {
            $student_filter = $student->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('nis', 'like', '%' . $search . '%')
                    ->orWhereHas('class', function($qc) use($search) {
                        $qc->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $student_count_filter = $student_filter->count();
        $student_data = $student_filter->limit($limit)
            ->offset($start)
            ->orderBy('updated_at')
            ->get();

        $student_arr = [];

        foreach ($student_data as $s) {
            $push = $s->toArray();
            $push['bills'] = [];

            $bills_year = (empty($s->bills)) ? [] : json_decode($s->bills, true);

            if (isset($bills_year['Y' . $year])) {
                $bills = $bills_year['Y' . $year];

                foreach ($bills as $b) {
                    $bill = Bill::select('id', 'id_type', 'name', 'nominal')
                        ->with(['type' => fn($query) => $query->select('id', 'period')])
                        ->whereId($b)
                        ->first();

                    switch ($bill->type->period->value) {
                        case BillPeriod::OneTime->value:
                        $icon = 'bx bx-calendar-event';
                        break;

                        case BillPeriod::Monthly->value:
                        $icon = 'bx bx-calendar';
                        break;

                        default:
                        $icon = 'bx bx-calendar-week';
                    }

                    array_push($push['bills'], [
                        'id' => $bill->id,
                        'name' => $bill->name,
                        'nominal' => $bill->nominal,
                        'icon' => $icon,
                        'period' => $bill->type->period_name
                    ]);
                }
            }

            array_push($student_arr, $push);
        }

        $response = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $student_count,
            'recordsFiltered' => $student_count_filter,
            'data' => $student_arr
        ];

        return response()->json($response);
    }

    public function datatableDiscount(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $discount = BillDiscount::select('id', 'id_year', 'id_bill', 'id_student', 'nominal', 'applies_to', 'status')
            ->with([
                'year' => fn($query) => $query->select('id', 'start_year', 'end_year'),
                'bill' => function ($query) {
                    $query->select('id', 'id_type', 'name')
                        ->with(['type' => fn($qt) => $qt->select('id', 'name', 'period')]);
                },
                'student' => function($query) {
                    $query->select('id', 'id_class', 'name')
                        ->with(['class' => fn($qc) => $qc->select('id', 'name')]);
                },
            ])
            ->withoutGlobalScopes([ActiveScope::class])
            ->when(!empty($request->status), fn($query) => $query->whereStatus($request->status));

        $discount_count = $discount->count();
        $discount_filter = $discount
            ->when(!empty($search), function($query) use($search) {
                $query->whereHas('year', function ($qy) use ($search) {
                        $qy->where('start_year', 'like', '%' . $search . '%')
                            ->orWhere('end_year', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('bill', function ($qt) use ($search) {
                        $qt->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('student', function ($qs) use ($search) {
                        $qs->where('name', 'like', '%' . $search . '%')
                            ->orWhereHas('class', function ($qc) use ($search) {
                                $qc->where('name', 'like', '%' . $search . '%');
                            });
                    });
            });

        $discount_count_filter = $discount_filter->count();
        $discount_data = $discount_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $discount_arr = [];

        foreach ($discount_data as $b) {
            $push = $b->toArray();
            $push['encrypted_id'] = $b->encrypted_id;
            $push['year_name'] = $b->year->year_name;
            $push['status_badge'] = $b->status_badge;

            array_push($discount_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $discount_count,
            'recordsFiltered' => $discount_count_filter,
            'data' => $discount_arr
        ]);
    }

    public function createType()
    {
        $periods = Common::option('bill_period');
        $spp_options = Common::option('yesno');

        return view($this->path['type'] . 'create', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['type']),
            'icon' => $this->icon,
            'periods' => $periods,
            'spp_options' => $spp_options,
        ]);
    }

    public function createSetup()
    {
        $period_onetime = BillPeriod::OneTime->value;
        $period_monthly = BillPeriod::Monthly->value;
        $types = BillType::select('id', 'name', 'period')->orderBy('name')->get();
        $years = Year::selectRaw('id, CONCAT("Thn. Ajaran : ", start_year, " - ", end_year) AS start_year')->orderBy('start_year', 'desc')->pluck('start_year', 'id');
        $year = Year::select('id')->active()->first();
        $validity_years = Common::option('year');
        $validity_months = Common::option('month');

        $dates = [];

        for ($d=1; $d<=28; $d++)
            $dates[$d] = $d;

        return view($this->path['setup'] . 'create', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['setup']),
            'icon' => $this->icon,
            'types' => $types,
            'years' => $years,
            'year' => $year,
            'dates' => $dates,
            'period_onetime' => $period_onetime,
            'period_monthly' => $period_monthly,
            'validity_months' => $validity_months,
            'validity_years' => $validity_years,
        ]);
    }

    public function createDiscount()
    {
        $years = Year::selectRaw('id, CONCAT("Thn. Ajaran : ", start_year, " - ", end_year) AS start_year')->orderBy('start_year', 'desc')->pluck('start_year', 'id');

        return view($this->path['discount'] . 'create', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['discount']),
            'icon' => $this->icon,
            'years' => $years,
        ]);
    }

    public function storeType(BillTypeRequest $request)
    {
        BillType::create($request->all());

        return Redirect::route('finance.bill.type.index')->with('success', __('message.create_success', ['label' => __($this->title['type'])]));
    }

    public function storeSetup(BillSetupRequest $request)
    {
        if ($request->period == BillPeriod::OneTime->value) {
            $merge = [
                'billing_date' => date('Y-m-d', strtotime($request->billing_date1)),
                'due_date' => date('Y-m-d', strtotime($request->due_date1)),
            ];
        } else {
            $merge = [
                'billing_date' => date('Y-m') . '-' . $request->billing_date2,
                'due_date' => date('Y-m') . '-' . $request->due_date2,
            ];
        }

        $request->merge($merge);
        Bill::create($request->all());

        return Redirect::route('finance.bill.setup.index')->with('success', __('message.create_success', ['label' => __($this->title['setup'])]));
    }

    public function storeDiscount(BillDiscountRequest $request)
    {
        $st = explode(' - ', $request->student);
        $student = Student::select('id')->whereNis($st[0])->first();

        $request->merge(['id_student' => $student->id]);
        BillDiscount::create($request->all());

        return Redirect::route('finance.bill.discount.index')->with('success', __('message.create_success', ['label' => __($this->title['discount'])]));
    }

    public function generate(BillGenerateRequest $request)
    {
        DB::transaction(function() use($request) {
            $year = $request->id_year;
            $months = [];
            $bill = Bill::select('id', 'id_year', 'id_type', 'nominal', 'due_date', 'start_year', 'start_month', 'end_year', 'end_month', 'billing_date')
                ->with([
                    'year' => fn($query) => $query->select('id', 'start_year', 'start_month', 'end_year', 'end_month'),
                    'type' => fn($query) => $query->select('id', 'period'),
                ])
                ->whereId($request->id_bill)
                ->first();

            if ($bill->type->is_period_monthly) {
                $semester = 1;
                $mm = 1;

                if ($bill->start_year == $bill->end_year) {
                    for ($i=(int) $bill->start_month; $i<=(int) $bill->end_month; $i++) {
                        array_push($months, ['m' => $i, 'y' => (int) $bill->start_year, 's' => $semester]);

                        if ($mm == 6)
                            $semester = 2;

                        $mm++;
                    }
                } else {
                    for ($i=(int) $bill->start_month; $i<=12; $i++) {
                        array_push($months, ['m' => $i, 'y' => (int) $bill->start_year, 's' => $semester]);

                        if ($mm == 6)
                            $semester = 2;

                        $mm++;
                    }

                    for ($i=1; $i<=(int) $bill->end_month; $i++) {
                        array_push($months, ['m' => $i, 'y' => (int) $bill->end_year, 's' => $semester]);

                        if ($mm == 6)
                            $semester = 2;

                        $mm++;
                    }
                }
            } else if ($bill->type->is_period_semiannual) {
                $start = $bill->year->start_year . '-' . Str::padLeft($bill->year->start_month, 2, '0') . '-01';
                $s1 = strtotime($start . ' +5 month');

                array_push($months, [
                    'm' => (int) date('n', $s1),
                    'y' => (int) date('Y', $s1),
                    's' => 1
                ]);

                array_push($months, [
                    'm' => (int) $bill->year->end_month,
                    'y' => (int) $bill->year->end_year,
                    's' => 2
                ]);
            } else {
                $time = strtotime($bill->billing_date);

                array_push($months, [
                    'm' => date('n', $time),
                    'y' => date('Y', $time),
                    's' => null
                ]);
            }

            if ($request->method == 'class') {
                $student = Student::select('id', 'bills')->whereIn('id_class', $request->class)->get();

                foreach ($student as $s) {
                    $bills = (empty($s->bills)) ? [] : json_decode($s->bills, true);
                    $index = 'Y' . $year;

                    if (!isset($bills[$index]))
                        $bills[$index] = [];

                    array_push($bills[$index], strval($bill->id));

                    Student::whereId($s->id)->update(['bills' => json_encode($bills)]);
                    $bill_add = 0;

                    foreach ($months as $m) {
                        $bill_add += $bill->nominal;

                        TransactionBill::create([
                            'id_student' => $s->id,
                            'id_bill' => $bill->id,
                            'semester' => $m['s'],
                            'months' => $m['m'],
                            'years' => $m['y'],
                            'subtotal' => $bill->nominal,
                            'total' => $bill->nominal,
                            'due_date' => ($bill->type->is_period_onetime) ? $bill->due_date : $m['y'] . '-' . Str::padLeft($m['m'], 2, '0') . '-' . Str::padLeft(date('j', strtotime($bill->due_date)), 2, '0')
                        ]);
                    }

                    $report = ReportStudent::select('bill_not_paid')->whereIdStudent($s->id)->first();
                    $bill_not_paid = (empty($report)) ? 0 : $report->bill_not_paid;

                    ReportStudent::updateOrCreate(['id_student' => $s->id], [
                        'bill_not_paid' => $bill_not_paid +  $bill_add
                    ]);
                }
            } else {
                foreach ($request->student as $s) {
                    $student = Student::select('id', 'bills')->whereId($s)->first();
                    $bills = (empty($student->bills)) ? [] : json_decode($student->bills, true);
                    $index = 'Y' . $year;

                    if (!isset($bills[$index]))
                        $bills[$index] = [];

                    array_push($bills[$index], strval($bill->id));

                    Student::whereId($student->id)->update(['bills' => json_encode($bills)]);
                    $bill_add = 0;

                    foreach ($months as $m) {
                        $bill_add += $bill->nominal;

                        TransactionBill::create([
                            'id_student' => $s,
                            'id_bill' => $bill->id,
                            'semester' => $m['s'],
                            'months' => $m['m'],
                            'years' => $m['y'],
                            'subtotal' => $bill->nominal,
                            'total' => $bill->nominal,
                            'due_date' => ($bill->type->is_period_onetime) ? $bill->due_date : $m['y'] . '-' . Str::padLeft($m['m'], 2, '0') . '-' . Str::padLeft(date('j', strtotime($bill->due_date)), 2, '0')
                        ]);
                    }

                    $report = ReportStudent::select('bill_not_paid')->whereIdStudent($s)->first();
                    $bill_not_paid = (empty($report)) ? 0 : $report->bill_not_paid;

                    ReportStudent::updateOrCreate(['id_student' => $s], [
                        'bill_not_paid' => $bill_not_paid +  $bill_add
                    ]);
                }
            }
        });

        $response = [
            'status' => true,
            'message' => __('message.generate_success', ['label' => __('label.bill')]),
        ];

        return response()->json($response);
    }

    public function editType(BillType $type)
    {
        $periods = Common::option('bill_period');
        $spp_options = Common::option('yesno');

        return view($this->path['type'] . 'edit', [
            'title' => __('label.edit') . ' ' . __($this->title['type']),
            'icon' => $this->icon,
            'type' => $type,
            'periods' => $periods,
            'spp_options' =>  $spp_options,
        ]);
    }

    public function editSetup(Bill $bill)
    {
        $period_onetime = BillPeriod::OneTime->value;
        $period_monthly = BillPeriod::Monthly->value;
        $types = BillType::select('id', 'name', 'period')->orderBy('name')->get();
        $years = Year::selectRaw('id, CONCAT("Thn. Ajaran : ", start_year, " - ", end_year) AS start_year')->orderBy('start_year', 'desc')->pluck('start_year', 'id');
        $year = Year::select('id')->active()->first();
        $validity_years = Common::option('year');
        $validity_months = Common::option('month');

        $dates = [];

        for ($d = 1; $d <= 28; $d++)
            $dates[$d] = $d;

        return view($this->path['setup'] . 'edit', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['setup']),
            'icon' => $this->icon,
            'bill' => $bill,
            'types' => $types,
            'years' => $years,
            'year' => $year,
            'dates' => $dates,
            'period_onetime' => $period_onetime,
            'period_monthly' => $period_monthly,
            'validity_months' => $validity_months,
            'validity_years' => $validity_years,
        ]);
    }

    public function editDiscount(BillDiscount $discount)
    {
        $years = Year::selectRaw('id, CONCAT("Thn. Ajaran : ", start_year, " - ", end_year) AS start_year')->orderBy('start_year', 'desc')->pluck('start_year', 'id');

        return view($this->path['discount'] . 'edit', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['discount']),
            'icon' => $this->icon,
            'discount' => $discount,
            'years' => $years,
        ]);
    }

    public function updateType(BillTypeRequest $request, BillType $type)
    {
        $type->update($request->all());

        return Redirect::route('finance.bill.type.index')->with('success', __('message.update_success', ['label' => __($this->title['type'])]));
    }

    public function updateSetup(BillSetupRequest $request, Bill $bill)
    {
        $error = false;

        if ($request->period == BillPeriod::OneTime->value) {
            $merge = [
                'billing_date' => date('Y-m-d', strtotime($request->billing_date1)),
                'due_date' => date('Y-m-d', strtotime($request->due_date1)),
            ];
        } else {
            $merge = [
                'billing_date' => date('Y-m') . '-' . $request->billing_date2,
                'due_date' => date('Y-m') . '-' . $request->due_date2,
            ];
        }

        DB::transaction(function() use($request, $merge, $bill, &$error) {
            $type = BillType::select('id', 'period')->whereId($request->id_type)->first();

            if ($bill->type->period->value != $type->period->value or $bill->id_year != $request->id_year or $bill->nominal != $request->nominal) {
                $paid = TransactionBill::whereIdBill($bill->id)->paid()->count();

                if ($paid > 0)
                    $error = __('string.bill_setup_change_cancel');

                if ($error == false) {
                    TransactionBill::whereIdBill($bill->id)->delete();

                    $student = Student::select('id', 'bills')->where('bills', 'like', '%"' . $bill->id . '"%')->get();

                    foreach ($student as $s) {
                        $bills = json_decode($s->bills, true);
                        $index = array_search($bill->id, $bills['Y' . $bill->id_year]);

                        unset($bills['Y' . $bill->id_year][$index]);

                        $s->bills = json_encode($bills);
                        $s->save();
                    }
                }
            }

            if ($error == false) {
                $request->merge($merge);
                $bill->update($request->all());
            } else
                DB::rollBack();
        });

        if ($error != false)
            return Redirect::route('finance.bill.setup.edit', $bill->encrypted_id)->withErrors($error)->withInput();

        return Redirect::route('finance.bill.setup.index')->with('success', __('message.update_success', ['label' => __($this->title['setup'])]));
    }

    public function updateDiscount(BillDiscountRequest $request, BillDiscount $discount)
    {
        $st = explode(' - ', $request->student);
        $student = Student::select('id')->whereNis($st[0])->first();

        $request->merge(['id_student' => $student->id]);
        $discount->update($request->all());

        return Redirect::route('finance.bill.discount.index')->with('success', __('message.update_success', ['label' => __($this->title['discount'])]));
    }

    public function destroy(Request $request)
    {
        $bill_paid = TransactionBill::whereIdStudent($request->student)->whereIdBill($request->bill)->paid()->count();

        if ($bill_paid > 0) {
            $response = [
                'status' => false,
                'message' => __('string.bill_can_not_delete')
            ];

            return response()->json($response);
        }

        DB::transaction(function() use($request) {
            $bill = Bill::select('id', 'id_type', 'nominal')
                ->with(['type' => fn($query) => $query->select('id', 'period')])
                ->whereId($request->bill)
                ->first();

            $student = Student::select('id', 'bills')->whereId($request->student)->first();
            $year_index = 'Y' . $request->year;
            $bills = json_decode($student->bills, true);
            $bill_index = array_search($bill->id, $bills[$year_index]);

            unset($bills[$year_index][$bill_index]);
            sort($bills[$year_index]);

            Student::whereId($student->id)->update(['bills' => json_encode($bills)]);

            $bill_not_paid = TransactionBill::whereIdStudent($request->student)->whereIdBill($request->bill)->notPaid()->sum('total');

            if ($bill_not_paid > 0) {
                TransactionBill::whereIdStudent($request->student)->whereIdBill($request->bill)->delete();
                ReportStudent::whereIdStudent($student->id)->decrement('bill_not_paid', $bill_not_paid);
            }
        });

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title_prefix)])
        ];

        return response()->json($response);
    }

    public function destroyType(BillType $type)
    {
        $type->delete();

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title['type'])])
        ];

        return response()->json($response);
    }

    public function destroySetup(Bill $bill)
    {
        $error = false;

        DB::transaction(function() use($bill, &$error) {
            $paid = TransactionBill::whereIdBill($bill->id)->paid()->count();

            if ($paid > 0)
                $error = __('string.bill_setup_delete_cancel');

            if ($error == false) {
                TransactionBill::whereIdBill($bill->id)->delete();

                $student = Student::select('id', 'bills')->where('bills', 'like', '%"' . $bill->id . '"%')->get();

                foreach ($student as $s) {
                    $bills = json_decode($s->bills, true);
                    $index = array_search($bill->id, $bills['Y' . $bill->id_year]);

                    unset($bills['Y' . $bill->id_year][$index]);

                    $s->bills = json_encode($bills);
                    $s->save();
                }
            }

            if ($error == false)
                $bill->delete();
            else
                DB::rollBack();
        });

        if ($error == false) {
            $response = [
                'status' => true,
                'message' => __('message.delete_success', ['label' => __($this->title['setup'])])
            ];
        } else {
            $response = [
                'status' => false,
                'message' => $error
            ];
        }

        return response()->json($response);
    }

    public function destroyDiscount(BillDiscount $discount)
    {
        $discount->delete();

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title['discount'])])
        ];

        return response()->json($response);
    }

    public function get(Request $request) // Get bill detail for discount form
    {
        $bill = Bill::select('id', 'id_type', 'start_month', 'start_year', 'end_month', 'end_year')
        ->with(['type' => fn($qt) => $qt->select('id', 'name', 'period')])
        ->whereId($request->id)
            ->firstOrFail();

        $option = '<option value=""></option>';

        if ($bill->type->is_period_monthly) { // Per Bulan
            $period = 'monthly';
            $start = $bill->start_year . '-' . $bill->start_month . '-01';
            $end = $bill->end_year . '-' . $bill->end_month . '-01';

            $s = new DateTime('2024-07-01');
            $e = new DateTime('2025-06-01');
            $diff = $e->diff($s);
            $date = $start;

            for ($i=0; $i<=$diff->format('%m'); $i++) {
                $time = strtotime($date);
                $option .= '<option value="' . date('Y-m', $time) . '">' . Common::monthFormat(date('n', $time), 'mmm') . ' ' . date('Y', $time) . '</option>';
                $date = date('Y-m-d', strtotime($date . ' +1 month'));
            }
        } else if ($bill->type->is_period_semiannual) { // Per Semester
            $period = 'semester';
            $option = '<option value="1">Semester 1</option><option value="1">Semester 2</option>';
        } else
            $period = 'once';

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'period' => $period,
                'option' => $option,
            ]
        ];

        return response()->json($response);
    }

    public function getOption(Request $request)
    {
        $options = '<option value=""></option>';
        $bill = Bill::select('id', 'name', 'nominal')
            ->whereIdYear($request->id_year)
            ->orderBy('name')
            ->get();

        foreach ($bill as $b)
            $options .= '<option value="' . $b->id . '">' . $b->name . ' - Rp. ' . number_format($b->nominal, 0, '', '.') . '</option>';

        return response()->json(['option' => $options]);
    }

    public function getClass(Request $request)
    {
        $class = Classroom::select('id', 'name')->whereLevelEducation($request->education);

        if (!empty($request->class))
            $class = $class->whereLevelClass($request->class);

        $class = $class->orderBy('name')->get();

        $view = view($this->path['setup'] . 'setting-class', [
            'class' => $class
        ])->render();

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'view' => $view
            ]
        ];

        return response()->json($response);
    }
}
