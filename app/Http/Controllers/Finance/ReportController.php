<?php

namespace App\Http\Controllers\Finance;

use App\Constants\EducationLevel as ConstantsEducationLevel;
use App\Enums\BillPeriod;
use App\Enums\BillType;
use App\Enums\EducationLevel;
use App\Enums\TransactionStatus;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Classroom;
use App\Models\Donation;
use App\Models\DonationHistory;
use App\Models\ReportBill;
use App\Models\ReportBillMethod;
use App\Models\Student;
use App\Models\TempFile;
use App\Models\TransactionBill;
use App\Models\Year;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    private $title_prefix = 'label.report';
    private $title = [
        'bill-student' => 'label.bill_per_student',
        'bill-not-paid' => 'label.bill_not_paid',
        'bill-progress' => 'label.bill_progress',
        'bill-total' => 'label.bill_total',
        'payment-method' => 'label.payment_method',
        'donation' => 'label.donation',
        'outstanding-arrears' => 'label.outstanding_arrears',
        'ongoing-collection-spp' => 'label.ongoing_collection_spp',
    ];
    private $icon = 'bx bx-file';
    private $path = 'backend.finance.report.';

    public function billNotPaid()
    {
        $education_levels = Common::option('education_level');
        $year = Year::select('id')->active()->first();
        $years = Year::selectRaw('id, CONCAT("Thn. Ajaran : ", start_year, " - ", end_year) AS start_year')->orderBy('start_year', 'desc')->pluck('start_year', 'id');

        return view($this->path . 'bill-not-paid', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['bill-not-paid']),
            'icon' => $this->icon,
            'education_levels' => $education_levels,
            'year' => $year,
            'years' => $years,
        ]);
    }

    public function billStudent()
    {
        $status_paid = TransactionStatus::Paid->value;
        $year = Year::select('id')->active()->first();
        $years = Year::selectRaw('id, CONCAT("Thn. Ajaran : ", start_year, " - ", end_year) AS start_year')->orderBy('start_year', 'desc')->pluck('start_year', 'id');

        return view($this->path . 'bill-student', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['bill-student']),
            'icon' => $this->icon,
            'status_paid' => $status_paid,
            'year' => $year,
            'years' => $years,
        ]);
    }

    public function billProgress(Request $request)
    {
        if ($request->has('year'))
            $year = $request->year;
        else {
            $y = Year::select('id')->active()->first();
            $year = $y->id;
        }

        $classes = ConstantsEducationLevel::Classes;
        $bill_progress = [];
        $sum_total = 0;
        $sum_paid = 0;
        $sum_remaining = 0;

        foreach ($classes as $c) {
            $bill_progress[$c] = ['data' => [], 'total' => 0, 'paid' => 0, 'remaining' => 0, 'progress' => 0];

            $report = ReportBill::select('id', 'id_type', 'total', 'paid', 'remaining')
                ->with(['type' => fn($query) => $query->select('id', 'name')])
                ->whereIdYear($year)
                ->whereLevel($c)
                ->get();

            foreach ($report as $r) {
                $progress = ($r->total == 0) ? 0 : ($r->paid / $r->total) * 100;
                $bill_progress[$c]['total'] += $r->total;
                $bill_progress[$c]['paid'] += $r->paid;
                $bill_progress[$c]['remaining'] += $r->remaining;

                $sum_total += $r->total;
                $sum_paid += $r->paid;
                $sum_remaining += $r->remaining;

                if ($progress >= 90)
                    $progress_color = 'bg-success';
                else if ($progress >= 26)
                    $progress_color = 'bg-primary';
                else
                    $progress_color = 'bg-danger';

                array_push($bill_progress[$c]['data'], (object) [
                    'type' => $r->type->name,
                    'total' => $r->total,
                    'paid' => $r->paid,
                    'remaining' => $r->remaining,
                    'progress' => $progress,
                    'progress_color' => $progress_color,
                ]);
            }

            $bill_progress[$c]['progress'] = ($bill_progress[$c]['total'] == 0) ? 0 : ($bill_progress[$c]['paid'] / $bill_progress[$c]['total']) * 100;

            if ($bill_progress[$c]['progress'] >= 90)
                $bill_progress[$c]['progress_color'] = 'bg-success';
            else if ($bill_progress[$c]['progress'] >= 26)
                $bill_progress[$c]['progress_color'] = 'bg-primary';
            else
                $bill_progress[$c]['progress_color'] = 'bg-danger';
        }

        $sum_progress = ($sum_total == 0) ? 0 : ($sum_paid / $sum_total) * 100;

        if ($sum_progress >= 90)
            $sum_progress_color = 'bg-success';
        else if ($sum_progress >= 26)
            $sum_progress_color = 'bg-primary';
        else
            $sum_progress_color = 'bg-danger';

        $years = Year::selectRaw('id, CONCAT("Thn. Ajaran : ", start_year, " - ", end_year) AS start_year')->orderBy('start_year', 'desc')->pluck('start_year', 'id');

        return view($this->path . 'bill-progress', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['bill-progress']),
            'icon' => $this->icon,
            'year' => $year,
            'years' => $years,
            'bill_progress' => $bill_progress,
            'sum' => (object) [
                'total' => $sum_total,
                'paid' => $sum_paid,
                'remaining' => $sum_remaining,
                'progress' => $sum_progress,
                'progress_color' => $sum_progress_color,
            ]
        ]);
    }

    public function billTotal(Request $request)
    {
        $education_levels = Common::option('education_level');
        $year = Year::select('id')->active()->first();
        $years = Year::selectRaw('id, CONCAT("Thn. Ajaran : ", start_year, " - ", end_year) AS start_year')->orderBy('start_year', 'desc')->pluck('start_year', 'id');

        return view($this->path . 'bill-total', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['bill-total']),
            'icon' => $this->icon,
            'education_levels' => $education_levels,
            'year' => $year,
            'years' => $years,
        ]);
    }

    public function paymentMethod(Request $request)
    {
        if ($request->has('year'))
            $year = $request->year;
        else {
            $y = Year::select('id')->active()->first();
            $year = $y->id;
        }

        $filter_start = (empty($request->start)) ? date('Y-m') . '-01' : $request->start;
        $filter_end = (empty($request->end)) ? date('Y-m-t') : $request->end;

        if ($filter_start == $filter_end) {
            $rr_cash = ReportBillMethod::select('total')->cash()->whereIdYear($year)->whereDates($filter_start)->first();
            $rr_bni = ReportBillMethod::select('total')->bni()->whereIdYear($year)->whereDates($filter_start)->first();
            $rr_bsi = ReportBillMethod::select('total')->bsi()->whereIdYear($year)->whereDates($filter_start)->first();
            $rr_cash = ReportBillMethod::select('total')->whereIdYear($year)->topupBalance()->whereDates($filter_start)->first();

            $cash = (empty($rr_cash)) ? 0 : $rr_cash->total;
            $bni = (empty($rr_bni)) ? 0 : $rr_bni->total;
            $bsi = (empty($rr_bsi)) ? 0 : $rr_cash->total;
            $topup = (empty($rr_topup)) ? 0 : $rr_topup->total;
        } else {
            $cash = ReportBillMethod::cash()
                ->whereIdYear($year)
                ->where(function($query) use($filter_start, $filter_end) {
                    $query->whereBetween('dates', [$filter_start, $filter_end]);
                })
                ->sum('total');

            $bni = ReportBillMethod::bni()
                ->whereIdYear($year)
                ->where(function($query) use($filter_start, $filter_end) {
                    $query->whereBetween('dates', [$filter_start, $filter_end]);
                })
                ->sum('total');

            $bsi = ReportBillMethod::bsi()
                ->whereIdYear($year)
                ->where(function($query) use($filter_start, $filter_end) {
                    $query->whereBetween('dates', [$filter_start, $filter_end]);
                })
                ->sum('total');

            $topup = ReportBillMethod::topupBalance()
                ->whereIdYear($year)
                ->where(function($query) use($filter_start, $filter_end) {
                    $query->whereBetween('dates', [$filter_start, $filter_end]);
                })
                ->sum('total');
        }

        $years = Year::selectRaw('id, CONCAT("Thn. Ajaran : ", start_year, " - ", end_year) AS start_year')->orderBy('start_year', 'desc')->pluck('start_year', 'id');

        return view($this->path . 'payment-method', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['payment-method']),
            'icon' => $this->icon,
            'year' => $year,
            'years' => $years,
            'cash' => $cash,
            'bni' => $bni,
            'bsi' => $bsi,
            'topup' => $topup,
            'filter' => (object) [
                'start' => $filter_start,
                'end' => $filter_end,
            ]
        ]);
    }

    public function donation()
    {
        $filter_start = date('Y-m') . '-01';
        $filter_end = date('Y-m-t');
        $donaturs = Donation::select('id', 'name')->orderBy('name')->pluck('name', 'id');

        return view($this->path . 'donation', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['donation']),
            'icon' => $this->icon,
            'donaturs' => $donaturs,
            'filter' => (object) [
                'start' => $filter_start,
                'end' => $filter_end,
            ]
        ]);
    }

    public function outstandingArrears(Request $request)
    {
        $months = Common::option('month');
        $years = Common::option('year');
        $education_levels = Common::option('education_level');
        $school_years = Year::selectRaw('id, CONCAT("Thn. Ajaran : ", start_year, " - ", end_year) AS start_year')->orderBy('start_year', 'desc')->pluck('start_year', 'id');
        $school_year = Year::select('id')->active()->first();
        $yesno = Common::option('yesno');

        return view($this->path . 'outstanding-arrears', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['outstanding-arrears']),
            'icon' => $this->icon,
            'months' => $months,
            'years' => $years,
            'education_levels' => $education_levels,
            'school_year' => $school_year,
            'school_years' => $school_years,
            'yesno' => $yesno,
        ]);
    }

    /* Penerimaan SPP Berjalan  */
    public function ongoingCollectionSpp(Request $request)
    {
        $school_years = Year::selectRaw('id, CONCAT("Thn. Ajaran : ", start_year, " - ", end_year) AS start_year')->orderBy('start_year', 'desc')->pluck('start_year', 'id');
        $months = Common::option('month');
        $years = Common::option('year');
        $education_levels = Common::option('education_level');

        return view($this->path . 'ongoing-collection-spp', [
            'title' => __($this->title_prefix) . ' - ' . __($this->title['ongoing-collection-spp']),
            'icon' => $this->icon,
            'months' => $months,
            'years' => $years,
            'school_years' => $school_years,
            'education_levels' => $education_levels,
        ]);
    }

    public function datatableBillNotPaid(Request $request)
    {
        $year = $request->year;
        $education = $request->education;
        $class = $request->class;
        $period_monthly = BillPeriod::Monthly->value;
        $period_semester = BillPeriod::Semiannual->value;

        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $bill = TransactionBill::select('id', 'id_student', 'id_bill', 'semester', 'months', 'years', 'total')
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'id_class', 'nis', 'name')->with(['class' => fn($qc) => $qc->select('id', 'name')]);
                },
                'bill' => function ($query) {
                    $query->select('id', 'id_year', 'id_type', 'name')
                    ->with([
                        'year' => fn($qt) => $qt->select('id', 'start_year', 'end_year'),
                        'type' => fn($qt) => $qt->select('id', 'name', 'period'),
                    ]);
                },
            ])
            ->notPaid()
            ->whereHas('student', function($query) use($education, $class) {
                $query->whereHas('class', function($qc) use($education, $class) {
                    $qc->whereLevelEducation($education)->whereLevelClass($class);
                });

            })
            ->whereHas('bill', function ($query) use ($year) {
                $query->whereIdYear($year);
            });

        $bill_count = $bill->count();
        $bill_filter = $bill->where(function ($query) use ($search) {
            $query->whereHas('bill', function ($qb) use ($search) {
                    $qb->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('student', function ($qs) use ($search) {
                    $qs->where('nis', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%');
                });
        });

        $bill_count_filter = $bill_filter->count();
        $bill_data = $bill_filter->limit($limit)
            ->offset($start)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        $bill_arr = [];

        foreach ($bill_data as $b) {
            $push = $b->toArray();
            $push['encrypted_id'] = $b->encrypted_id;
            $push['year'] = $b->bill->year->year_name;
            $push['bill_name'] = $b->bill->name;

            if ($b->bill->type->period->value == $period_monthly)
                $push['bill_name'] .= ' Bulan ' . Common::monthFormat($b->months) . ' ' . $b->years;
            else if ($b->bill->type->period->value == $period_semester)
                $push['bill_name'] .= ' Semester ' . $b->semester;

            array_push($bill_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $bill_count,
            'recordsFiltered' => $bill_count_filter,
            'data' => $bill_arr
        ]);
    }

    public function datatableBillStudent(Request $request)
    {
        $year = $request->year;
        $st = explode(' - ', $request->student);
        $student = Student::select('id')->whereNis(trim($st[0]))->first();
        $period_monthly = BillPeriod::Monthly->value;
        $period_semester = BillPeriod::Semiannual->value;

        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $bill = TransactionBill::select('id', 'id_student', 'id_bill', 'id_transaction', 'semester', 'months', 'years', 'total', 'status')
            ->with([
                'student' => function($query) {
                    $query->select('id', 'id_class', 'name')->with(['class' => fn($qc) => $qc->select('id', 'name')]);
                },
                'bill' => function($query) {
                    $query->select('id', 'id_year', 'id_type', 'name')
                        ->with([
                            'year' => fn($qt) => $qt->select('id', 'start_year', 'end_year'),
                            'type' => fn($qt) => $qt->select('id', 'name', 'period'),
                        ]);
                },
                'transaction' => fn($query) => $query->select('id', 'paid_at'),
            ])
            ->whereIdStudent(@$student->id)
            ->whereHas('bill', function($query) use($year) {
                $query->whereIdYear($year);
            });

        if (!empty($request->bill))
            $bill = $bill->whereIdBill($request->bill);

        $bill_count = $bill->count();
        $bill_filter = $bill->where(function ($query) use ($search) {
            $query->whereHas('transaction', function($qt) use($search) {
                    $qt->where('paid_at', 'like', '%' . $search . '%');
                })
                ->orWhereHas('bill', function($qb) use($search) {
                    $qb->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('student', function ($qs) use ($search) {
                    $qs->where('name', 'like', '%' . $search . '%')
                        ->orWhereHas('class', function($qc) use($search) {
                            $qc->where('name', 'like', '%' . $search . '%');
                        });
                });
        });

        $bill_count_filter = $bill_filter->count();
        $bill_data = $bill_filter->limit($limit)
            ->offset($start)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        $bill_arr = [];

        foreach ($bill_data as $b) {
            $push = $b->toArray();
            $push['encrypted_id'] = $b->encrypted_id;
            $push['status_badge'] = $b->status_badge;
            $push['year'] = $b->bill->year->year_name;
            $push['bill_name'] = $b->bill->name;

            if ($b->bill->type->period->value == $period_monthly)
                $push['bill_name'] .= ' Bulan ' . Common::monthFormat($b->months) . ' ' . $b->years;
            else if ($b->bill->type->period->value == $period_semester)
                $push['bill_name'] .= ' Semester ' . $b->semester;

            array_push($bill_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $bill_count,
            'recordsFiltered' => $bill_count_filter,
            'data' => $bill_arr
        ]);
    }

    public function datatableBillTotal(Request $request)
    {
        $year = $request->year;
        $education = $request->education;
        $class = $request->class;
        $classroom = $request->classroom;
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $student = Student::select('id', 'id_class', 'nis', 'name')
            ->with(['class' => fn($qc) => $qc->select('id', 'name')]);

        if (empty($classroom)) {
            $student = $student->whereHas('class', function ($query) use ($education, $class) {
                $query->whereLevelEducation($education)->whereLevelClass($class);
            });
        } else {
            $student = $student->whereIdClass($classroom);
        }

        $student_count = $student->count();
        $student_filter = $student->where(function ($query) use ($search) {
            $query->where('nis', 'like', '%' . $search . '%')
                ->orWhere('name', 'like', '%' . $search . '%')
                ->orWhereHas('class', function ($qc) use ($search) {
                    $qc->where('name', 'like', '%' . $search . '%');
                });
        });
        $student_count_filter = $student_filter->count();
        $student_data = $student_filter->limit($limit)
            ->offset($start)
            ->orderBy('name')
            ->get();

        $student_arr = [];

        foreach ($student_data as $s) {
            $bill = TransactionBill::with(['bill' => fn($query) => $query->select('id')])
                ->whereIdStudent($s->id)
                ->whereHas('bill', function ($query) use ($year) {
                    $query->whereIdYear($year);
                })
                ->sum('total');

            $push = $s->toArray();
            $push['total'] = $bill;

            array_push($student_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $student_count,
            'recordsFiltered' => $student_count_filter,
            'data' => $student_arr
        ]);
    }

    public function datatableDonation(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $donation = DonationHistory::select('id', 'id_donation', 'id_student', 'id_transaction', 'nominal', 'paid_at')
            ->with([
                'student' => function($query) {
                    $query->select('id', 'id_class', 'nis', 'name')->with(['class' => fn($qc) => $qc->select('id', 'name')]);
                },
                'donation' => fn($query) => $query->select('id', 'name'),
                'transaction' => fn($query) => $query->select('id', 'number'),
            ])
            ->where(function($query) use($request) {
                $query->whereBetween('paid_at', [$request->start_date, $request->end_date]);
            });

        if (!empty($request->donatur))
            $donation = $donation->whereIdDonation($request->donatur);

        $donation_count = $donation->count();
        $donation_filter = $donation->where(function ($query) use ($search) {
            $query->where('paid_at', 'like', '%' . $search . '%')
                ->orWhereHas('donation', function($qd) use($search) {
                    $qd->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('student', function ($qs) use ($search) {
                    $qs->where('nis', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhereHas('class', function($qc) use($search) {
                            $qc->where('name', 'like', '%' . $search . '%');
                        });
                });
        });

        $donation_count_filter = $donation_filter->count();
        $donation_data = $donation_filter->limit($limit)
            ->offset($start)
            ->orderBy('paid_at', 'desc')
            ->get();

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $donation_count,
            'recordsFiltered' => $donation_count_filter,
            'data' => $donation_data
        ]);
    }

    public function downloadPdfBillNotPaid(Request $request)
    {
        $year = $request->year;
        $education = $request->education;

        switch ($education) {
            case EducationLevel::SD->value:
                $class = [
                    '1' => '1 SD',
                    '2' => '2 SD',
                    '3' => '3 SD',
                    '4' => '4 SD',
                    '5' => '5 SD',
                    '6' => '6 SD',
                ];
                break;

            case EducationLevel::SMP->value:
                $class = [
                    '1' => '1 SMP',
                    '2' => '2 SMP',
                    '3' => '3 SMP',
                ];
                break;

            default:
                $class = [
                    '1' => '1 SMA',
                    '2' => '2 SMA',
                    '3' => '3 SMA',
                ];
        }

        $class_name = $class[$request->class];
        $period_monthly = BillPeriod::Monthly->value;
        $period_semester = BillPeriod::Semiannual->value;

        $bill = TransactionBill::select('id', 'id_student', 'id_bill', 'semester', 'months', 'years', 'total')
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'id_class', 'nis', 'name')->with(['class' => fn($qc) => $qc->select('id', 'name')]);
                },
                'bill' => function ($query) {
                    $query->select('id', 'id_year', 'id_type', 'name')
                    ->with([
                        'year' => fn($qt) => $qt->select('id', 'start_year', 'end_year'),
                        'type' => fn($qt) => $qt->select('id', 'name', 'period'),
                    ]);
                },
            ])
            ->notPaid()
            ->whereHas('student', function($query) use($education, $class) {
                $query->whereHas('class', function($qc) use($education, $class) {
                    $qc->whereLevelEducation($education)->whereLevelClass($class);
                });
            })
            ->whereHas('bill', function($query) use($year) {
                $query->whereIdYear($year);
            })
            ->orderBy('due_date')
            ->get();

        $pdf = PDF::loadView($this->path . 'pdf.bill-not-paid', [
            'education' => $education,
            'class' => $class_name,
            'bill' => $bill,
            'period' => (object) [
                'monthly' => $period_monthly,
                'semester' => $period_semester,
            ]
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download(str_replace(' ', '-', strtolower(__('label.report_bill_not_paid'))) . '-' . date('YmdHis') . '.pdf');
    }

    public function downloadPdfBillStudent(Request $request)
    {
        $year = $request->year;
        $st = explode(' - ', $request->student);
        $student = Student::select('id', 'nis', 'name')->whereNis(trim($st[0]))->first();
        $status_paid = TransactionStatus::Paid->value;
        $bill_name = '';
        $period_monthly = BillPeriod::Monthly->value;
        $period_semester = BillPeriod::Semiannual->value;

        $transaction = TransactionBill::select('id', 'id_student', 'id_bill', 'id_transaction', 'semester', 'months', 'years', 'total', 'status')
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'id_class', 'name')->with(['class' => fn($qc) => $qc->select('id', 'name')]);
                },
                'bill' => function ($query) {
                    $query->select('id', 'id_year', 'id_type', 'name')
                        ->with([
                            'year' => fn($qt) => $qt->select('id', 'start_year', 'end_year'),
                            'type' => fn($qt) => $qt->select('id', 'name', 'period'),
                        ]);
                },
                'transaction' => fn($query) => $query->select('id', 'paid_at'),
            ])
            ->whereIdStudent(@$student->id)
            ->whereHas('bill', function ($query) use ($year) {
                $query->whereIdYear($year);
            });

        if (!empty($request->bill)) {
            $bill = Bill::select('name')->whereId($request->bill)->first();
            $bill_name = $bill->name;
            $transaction = $transaction->whereIdBill($request->bill);
        }

        $transaction = $transaction->orderBy('due_date')->get();

        $pdf = PDF::loadView($this->path . 'pdf.bill-student', [
            'student' => $student,
            'transaction' => $transaction,
            'bill_name' => $bill_name,
            'status_paid' => $status_paid,
            'period' => (object) [
                'monthly' => $period_monthly,
                'semester' => $period_semester,
            ]
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download(str_replace(' ', '-', strtolower(__('label.report_bill_per_student'))) . '-' . date('YmdHis') . '.pdf');
    }

    public function downloadPdfBillProgress(Request $request)
    {
        $year = $request->year;
        $classes = ConstantsEducationLevel::Classes;
        $bill_progress = [];
        $sum_total = 0;
        $sum_paid = 0;
        $sum_remaining = 0;

        foreach ($classes as $c) {
            $bill_progress[$c] = ['data' => [], 'total' => 0, 'paid' => 0, 'remaining' => 0, 'progress' => 0];

            $report = ReportBill::select('id', 'id_type', 'total', 'paid', 'remaining')
                ->with(['type' => fn($query) => $query->select('id', 'name')])
                ->whereIdYear($year)
                ->whereLevel($c)
                ->get();

            foreach ($report as $r) {
                $progress = ($r->total == 0) ? 0 : ($r->paid / $r->total) * 100;
                $bill_progress[$c]['total'] += $r->total;
                $bill_progress[$c]['paid'] += $r->paid;
                $bill_progress[$c]['remaining'] += $r->remaining;

                $sum_total += $r->total;
                $sum_paid += $r->paid;
                $sum_remaining += $r->remaining;

                if ($progress >= 90)
                    $progress_color = 'bg-success';
                else if ($progress >= 26)
                    $progress_color = 'bg-primary';
                else
                    $progress_color = 'bg-danger';

                array_push($bill_progress[$c]['data'], (object) [
                    'type' => $r->type->name,
                    'total' => $r->total,
                    'paid' => $r->paid,
                    'remaining' => $r->remaining,
                    'progress' => $progress,
                    'progress_color' => $progress_color,
                ]);
            }

            $bill_progress[$c]['progress'] = ($bill_progress[$c]['total'] == 0) ? 0 : ($bill_progress[$c]['paid'] / $bill_progress[$c]['total']) * 100;
        }

        $sum_progress = ($sum_total == 0) ? 0 : ($sum_paid / $sum_total) * 100;

        $pdf = PDF::loadView($this->path . 'pdf.bill-progress', [
            'bill_progress' => $bill_progress,
            'sum' => (object) [
                'total' => $sum_total,
                'paid' => $sum_paid,
                'remaining' => $sum_remaining,
                'progress' => $sum_progress,
            ]
        ]);

        $pdf->setPaper('A4');

        return $pdf->download(str_replace(' ', '-', strtolower(__('label.report_bill_progress'))) . '-' . date('YmdHis') . '.pdf');
    }

    public function downloadPdfBillTotal(Request $request)
    {
        $year = $request->year;
        $education = $request->education;
        $classroom = $request->classroom;

        switch ($education) {
            case EducationLevel::SD->value:
                $class = [
                    '1' => '1 SD',
                    '2' => '2 SD',
                    '3' => '3 SD',
                    '4' => '4 SD',
                    '5' => '5 SD',
                    '6' => '6 SD',
                ];
                break;

            case EducationLevel::SMP->value:
                $class = [
                    '1' => '1 SMP',
                    '2' => '2 SMP',
                    '3' => '3 SMP',
                ];
                break;

            default:
                $class = [
                    '1' => '1 SMA',
                    '2' => '2 SMA',
                    '3' => '3 SMA',
                ];
        }

        $class_name = $class[$request->class];
        $classroom_name = '';

        $student = Student::select('id', 'id_class', 'nis', 'name')
            ->with(['class' => fn($qc) => $qc->select('id', 'name')]);
        
        if (empty($classroom)) {
            $student = $student->whereHas('class', function ($query) use ($education, $class) {
                $query->whereLevelEducation($education)->whereLevelClass($class);
            });
        } else {
            $classroom = Classroom::select('id', 'name')->whereId($classroom)->first();
            $classroom_name = $classroom->name;
            $student = $student->whereIdClass($classroom->id);
        }
        
        $student = $student->orderBy('name')
            ->get();

        $pdf = PDF::loadView($this->path . 'pdf.bill-total', [
            'year' => $year,
            'education' => $education,
            'class' => $class_name,
            'classroom_name' => $classroom_name,
            'student' => $student
        ]);

        $pdf->setPaper('A4');

        return $pdf->download(str_replace(' ', '-', strtolower(__('label.report_bill_total'))) . '-' . date('YmdHis') . '.pdf');
    }

    public function downloadPdfOutstandingArrears(Request $request)
    {
        $tmp = TempFile::whereId(Crypt::decrypt($request->tmp))->firstOrFail();
        $tmp_file = Storage::get('tmp/' . $tmp->file);
        $data = json_decode($tmp_file);
        $class = '';

        if (!empty($request->class)) {
            $classroom = Classroom::select('name')->whereId($request->class)->first();
            $class = $classroom->name;
        }

        $pdf = PDF::loadView($this->path . 'pdf.outstanding-arrears', [
            'month' => $request->month,
            'year' => $request->year,
            'education' => $request->education,
            'class' => $class,
            'data' => $data,
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download(str_replace(' ', '-', strtolower(__('label.report_outstanding_arrears'))) . '-' . date('YmdHis') . '.pdf');
    }

    public function downloadPdfOngoingCollectionSpp(Request $request)
    {
        $tmp = TempFile::whereId(Crypt::decrypt($request->tmp))->firstOrFail();
        $tmp_file = Storage::get('tmp/' . $tmp->file);
        $data = json_decode($tmp_file);
        $class = '';

        if (!empty($request->class)) {
            $classroom = Classroom::select('name')->whereId($request->class)->first();
            $class = $classroom->name;
        }

        $pdf = PDF::loadView($this->path . 'pdf.ongoing-collection-spp', [
            'month' => $request->month,
            'year' => $request->year,
            'education' => $request->education,
            'class' => $class,
            'data' => $data,
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download(str_replace(' ', '-', strtolower(__('label.report_ongoing_collection_spp'))) . '-' . date('YmdHis') . '.pdf');
    }

    public function downloadPdfPaymentMethod(Request $request)
    {
        $filter_start = (empty($request->start_date)) ? date('Y-m') . '-01' : $request->start_date;
        $filter_end = (empty($request->end_date)) ? date('Y-m-t') : $request->end_date;

        if ($filter_start == $filter_end) {
            $rr_cash = ReportBillMethod::select('total')->cash()->whereDates($filter_start)->first();
            $rr_bni = ReportBillMethod::select('total')->bni()->whereDates($filter_start)->first();
            $rr_bsi = ReportBillMethod::select('total')->bsi()->whereDates($filter_start)->first();
            $rr_cash = ReportBillMethod::select('total')->topupBalance()->whereDates($filter_start)->first();

            $cash = (empty($rr_cash)) ? 0 : $rr_cash->total;
            $bni = (empty($rr_bni)) ? 0 : $rr_bni->total;
            $bsi = (empty($rr_bsi)) ? 0 : $rr_cash->total;
            $topup = (empty($rr_topup)) ? 0 : $rr_topup->total;
        } else {
            $cash = ReportBillMethod::cash()
                ->where(function ($query) use ($filter_start, $filter_end) {
                    $query->whereBetween('dates', [$filter_start, $filter_end]);
                })
                ->sum('total');

            $bni = ReportBillMethod::bni()
                ->where(function ($query) use ($filter_start, $filter_end) {
                    $query->whereBetween('dates', [$filter_start, $filter_end]);
                })
                ->sum('total');

            $bsi = ReportBillMethod::bsi()
                ->where(function ($query) use ($filter_start, $filter_end) {
                    $query->whereBetween('dates', [$filter_start, $filter_end]);
                })
                ->sum('total');

            $topup = ReportBillMethod::topupBalance()
                ->where(function ($query) use ($filter_start, $filter_end) {
                    $query->whereBetween('dates', [$filter_start, $filter_end]);
                })
                ->sum('total');
        }

        $pdf = PDF::loadView($this->path . 'pdf.payment-method', [
            'cash' => $cash,
            'bni' => $bni,
            'bsi' => $bsi,
            'topup' => $topup,
            'filter' => (object) [
                'start' => $filter_start,
                'end' => $filter_end,
            ]
        ]);

        $pdf->setPaper('A4');

        return $pdf->download(str_replace(' ', '-', strtolower(__('label.report_payment_method'))) . '-' . date('YmdHis') . '.pdf');
    }

    public function downloadPdfDonation(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $donatur_name = '';
        $donation = DonationHistory::select('id', 'id_donation', 'id_student', 'id_transaction', 'nominal', 'paid_at')
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'id_class', 'nis', 'name')->with(['class' => fn($qc) => $qc->select('id', 'name')]);
                },
                'donation' => fn($query) => $query->select('id', 'name'),
                'transaction' => fn($query) => $query->select('id', 'number'),
            ])
            ->where(function ($query) use ($start_date, $end_date) {
                $query->whereBetween('paid_at', [$start_date, $end_date]);
            });

        if (!empty($request->donatur)) {
            $donatur = Donation::select('name')->whereId($request->donatur)->first();
            $donatur_name = $donatur->name;
            $donation = $donation->whereIdDonation($request->donatur);
        }

        $donation = $donation->orderBy('paid_at', 'desc')->get();

        $pdf = PDF::loadView($this->path . 'pdf.donation', [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'donatur_name' => $donatur_name,
            'donation' => $donation,
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download(str_replace(' ', '-', strtolower(__('label.report_donation'))) . '-' . date('YmdHis') . '.pdf');
    }

    public function downloadExcelBillNotPaid(Request $request)
    {
        $year = $request->year;
        $education = $request->education;

        switch ($education) {
            case EducationLevel::SD->value:
                $class = [
                    '1' => '1 SD',
                    '2' => '2 SD',
                    '3' => '3 SD',
                    '4' => '4 SD',
                    '5' => '5 SD',
                    '6' => '6 SD',
                ];
                break;

            case EducationLevel::SMP->value:
                $class = [
                    '1' => '1 SMP',
                    '2' => '2 SMP',
                    '3' => '3 SMP',
                ];
                break;

            default:
                $class = [
                    '1' => '1 SMA',
                    '2' => '2 SMA',
                    '3' => '3 SMA',
                ];
        }

        $class_name = $class[$request->class];
        $period_monthly = BillPeriod::Monthly->value;
        $period_semester = BillPeriod::Semiannual->value;

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

        $sheet->setCellValue('A' . $row, __('label.report_bill_not_paid'));
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, __('label.level_education') . ' : ' . strtoupper($education));
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $row++;

        $sheet->setCellValue('A' . $row, __('label.level_class') . ' : ' . $class_name);
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $row += 2;

        // Table Header
        $sheet->setCellValue('A' . $row, __('label.no'));
        $sheet->setCellValue('B' . $row, __('label.school_year'));
        $sheet->setCellValue('C' . $row, __('label.bill_name'));
        $sheet->setCellValue('D' . $row, __('label.nis'));
        $sheet->setCellValue('E' . $row, __('label.student_name'));
        $sheet->setCellValue('F' . $row, __('label.total'));

        foreach ($cols as $c)
            $sheet->getStyle($c . $row)->applyFromArray($style_col);

        $row++;

        // Table Body
        $no = 1;
        $bill = TransactionBill::select('id', 'id_student', 'id_bill', 'semester', 'months', 'years', 'total')
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'id_class', 'nis', 'name')->with(['class' => fn($qc) => $qc->select('id', 'name')]);
                },
                'bill' => function ($query) {
                    $query->select('id', 'id_year', 'id_type', 'name')
                        ->with([
                            'year' => fn($qt) => $qt->select('id', 'start_year', 'end_year'),
                            'type' => fn($qt) => $qt->select('id', 'name', 'period'),
                        ]);
                },
            ])
            ->notPaid()
            ->whereHas('student', function ($query) use ($education, $class) {
                $query->whereHas('class', function ($qc) use ($education, $class) {
                    $qc->whereLevelEducation($education)->whereLevelClass($class);
                });
            })
            ->whereHas('bill', function($query) use($year) {
                $query->whereIdYear($year);
            })
            ->orderBy('due_date')
            ->get();

        foreach ($bill as $b) {
            $bill_name = $b->bill->name;

            if ($b->bill->type->period->value == $period_monthly)
                $bill_name .= ' - Bulan ' . Common::monthFormat($b->months) . ' ' . $b->years;
            else if ($b->bill->type->period->value == $period_semester)
                $bill_name .= ' - Semester ' . $b->semester;

            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $b->bill->year->year_name);
            $sheet->setCellValue('C' . $row, $bill_name);
            $sheet->setCellValue('D' . $row, $b->student->nis);
            $sheet->setCellValue('E' . $row, $b->student->name);
            $sheet->setCellValue('F' . $row, $b->total);

            foreach ($cols as $c)
                $sheet->getStyle($c . $row)->applyFromArray($style_row);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            $row++;
            $no++;
        }

        // Setting
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(50);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(35);
        $sheet->getColumnDimension('F')->setWidth(20);

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->setTitle(__('label.report_bill_not_paid'));

        // Output
        $filename = str_replace(' ', '-', strtolower(__('label.report_bill_not_paid'))) . '-' . date('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    public function downloadExcelBillStudent(Request $request)
    {
        $year = $request->year;
        $st = explode(' - ', $request->student);
        $student = Student::select('id', 'nis', 'name')->whereNis(trim($st[0]))->first();
        $period_monthly = BillPeriod::Monthly->value;
        $period_semester = BillPeriod::Semiannual->value;

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

        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $last_col = end($cols);
        $row = 1;

        $sheet->setCellValue('A' . $row, __('label.report_bill_per_student'));
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, __('label.nis') . ' : ' . $student->nis);
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $row++;

        $sheet->setCellValue('A' . $row, __('label.name') . ' : ' . $student->name);
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $row++;

        if (!empty($request->bill)) {
            $bill = Bill::select('name')->whereId($request->bill)->first();
            $sheet->setCellValue('A' . $row, __('label.bill') . ' : ' . $bill->name);
            $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
            $row += 2;
        } else
            $row++;

        // Table Header
        $sheet->setCellValue('A' . $row, __('label.no'));
        $sheet->setCellValue('B' . $row, __('label.school_year'));
        $sheet->setCellValue('C' . $row, __('label.bill_name'));
        $sheet->setCellValue('D' . $row, __('label.student_name'));
        $sheet->setCellValue('E' . $row, __('label.class'));
        $sheet->setCellValue('F' . $row, __('label.nominal'));
        $sheet->setCellValue('G' . $row, __('label.status'));
        $sheet->setCellValue('H' . $row, __('label.payment_date'));

        foreach ($cols as $c)
            $sheet->getStyle($c . $row)->applyFromArray($style_col);

        $row++;

        // Table Body
        $no = 1;
        $bill = TransactionBill::select('id', 'id_student', 'id_bill', 'id_transaction', 'semester', 'months', 'years', 'total', 'status')
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'id_class', 'name')->with(['class' => fn($qc) => $qc->select('id', 'name')]);
                },
                'bill' => function ($query) {
                    $query->select('id', 'id_year', 'id_type', 'name')
                        ->with([
                            'year' => fn($qt) => $qt->select('id', 'start_year', 'end_year'),
                            'type' => fn($qt) => $qt->select('id', 'name', 'period'),
                        ]);
                },
                'transaction' => fn($query) => $query->select('id', 'paid_at'),
            ])
            ->whereIdStudent(@$student->id)
            ->whereHas('bill', function ($query) use ($year) {
                $query->whereIdYear($year);
            });

        if (!empty($request->bill))
            $bill = $bill->whereIdBill($request->bill);

        $bill = $bill->orderBy('due_date')->get();

        foreach ($bill as $b) {
            $bill_name = $b->bill->name;

            if ($b->bill->type->period->value == $period_monthly)
                $bill_name .= ' - Bulan ' . Common::monthFormat($b->months) . ' ' . $b->years;
            else if ($b->bill->type->period->value == $period_semester)
                $bill_name .= ' - Semester ' . $b->semester;

            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $b->bill->year->year_name);
            $sheet->setCellValue('C' . $row, $bill_name);
            $sheet->setCellValue('D' . $row, $b->student->name);
            $sheet->setCellValue('E' . $row, $b->student->class->name);
            $sheet->setCellValue('F' . $row, $b->total);
            $sheet->setCellValue('G' . $row, $b->status_label);
            $sheet->setCellValue('H' . $row, ($b->status->value == TransactionStatus::Paid->value) ? Common::dateFormat($b->transaction->paid_at, 'dd mmmm yyyy, hh:ii WIB') : '-');

            foreach ($cols as $c)
                $sheet->getStyle($c . $row)->applyFromArray($style_row);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $row++;
            $no++;
        }

        // Setting
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(50);
        $sheet->getColumnDimension('D')->setWidth(35);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(35);

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->setTitle(__('label.report_bill_per_student'));

        // Output
        $filename = str_replace(' ', '-', strtolower(__('label.report_bill_per_student'))) . '-' . date('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    public function downloadExcelBillProgress(Request $request)
    {
        $year = $request->year;
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

        $sheet->setCellValue('A' . $row, __('label.report_bill_progress'));
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row += 2;

        // Table Header
        $sheet->setCellValue('A' . $row, __('label.no'));
        $sheet->setCellValue('B' . $row, __('label.payment_type'));
        $sheet->setCellValue('C' . $row, __('label.liability'));
        $sheet->setCellValue('D' . $row, __('label.paid_off2'));
        $sheet->setCellValue('E' . $row, __('label.less'));
        $sheet->setCellValue('F' . $row, __('label.progress'));

        foreach ($cols as $c)
            $sheet->getStyle($c . $row)->applyFromArray($style_col);

        $row++;

        // Table Body
        $no = 1;
        $classes = ConstantsEducationLevel::Classes;
        $sum_total = 0;
        $sum_paid = 0;
        $sum_remaining = 0;

        foreach ($classes as $index => $cl) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, __('label.level_class') . ' ' . $cl);
            $sheet->mergeCells('B' . $row . ':' . $last_col . $row);

            foreach ($cols as $c)
                $sheet->getStyle($c . $row)->applyFromArray($style_col);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;

            $total = 0;
            $paid = 0;
            $remaining = 0;
            $report = ReportBill::select('id', 'id_type', 'total', 'paid', 'remaining')
                ->with(['type' => fn($query) => $query->select('id', 'name')])
                ->whereIdYear($year)
                ->whereLevel($cl)
                ->get();

            foreach ($report as $index_r => $r) {
                $no_r = $index_r + 1;
                $progress = ($r->total == 0) ? 0 : ($r->paid / $r->total) * 100;
                $total += $r->total;
                $paid += $r->paid;
                $remaining += $r->remaining;
                $sum_total += $r->total;
                $sum_paid += $r->paid;
                $sum_remaining += $r->remaining;

                $sheet->setCellValue('B' . $row, $no . '.' . $no_r . ' : ' . $r->type->name);
                $sheet->setCellValue('C' . $row, $r->total);
                $sheet->setCellValue('D' . $row, $r->paid);
                $sheet->setCellValue('E' . $row, $r->remaining);
                $sheet->setCellValue('F' . $row, Common::decimalFormat($progress) . '%');

                foreach ($cols as $c)
                    $sheet->getStyle($c . $row)->applyFromArray($style_row);

                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $row++;
            }

            $progress = ($total == 0) ? 0 : ($paid / $total) * 100;

            $sheet->setCellValue('B' . $row, __('label.total') . ' ' . __('label.level_class') . ' ' . $index);
            $sheet->setCellValue('C' . $row, $total);
            $sheet->setCellValue('D' . $row, $paid);
            $sheet->setCellValue('E' . $row, $remaining);
            $sheet->setCellValue('F' . $row, Common::decimalFormat($progress) . '%');

            foreach ($cols as $c)
                $sheet->getStyle($c . $row)->applyFromArray($style_col);

            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $row++;
            $no++;
        }

        $sum_progress = ($sum_total == 0) ? 0 : ($sum_paid / $sum_total) * 100;

        $sheet->setCellValue('A' . $row, __('label.total'));
        $sheet->setCellValue('C' . $row, $sum_total);
        $sheet->setCellValue('D' . $row, $sum_paid);
        $sheet->setCellValue('E' . $row, $sum_remaining);
        $sheet->setCellValue('F' . $row, Common::decimalFormat($sum_progress) . '%');

        foreach ($cols as $c)
            $sheet->getStyle($c . $row)->applyFromArray($style_col);

        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Setting
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(50);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->setTitle(__('label.report_bill_progress'));

        // Output
        $filename = str_replace(' ', '-', strtolower(__('label.report_bill_progress'))) . '-' . date('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    public function downloadExcelBillTotal(Request $request)
    {
        $year = $request->year;
        $education = $request->education;
        $classroom = $request->classroom;

        switch ($education) {
            case EducationLevel::SD->value:
                $class = [
                    '1' => '1 SD',
                    '2' => '2 SD',
                    '3' => '3 SD',
                    '4' => '4 SD',
                    '5' => '5 SD',
                    '6' => '6 SD',
                ];
                break;

            case EducationLevel::SMP->value:
                $class = [
                    '1' => '1 SMP',
                    '2' => '2 SMP',
                    '3' => '3 SMP',
                ];
                break;

            default:
                $class = [
                    '1' => '1 SMA',
                    '2' => '2 SMA',
                    '3' => '3 SMA',
                ];
        }

        $class_name = $class[$request->class];
        $classroom_name = '';

        $student = Student::select('id', 'id_class', 'nis', 'name')
            ->with(['class' => fn($qc) => $qc->select('id', 'name')]);

        if (empty($classroom)) {
            $student = $student->whereHas('class', function ($query) use ($education, $class) {
                $query->whereLevelEducation($education)->whereLevelClass($class);
            });
        } else {
            $classroom = Classroom::select('id', 'name')->whereId($classroom)->first();
            $classroom_name = $classroom->name;
            $student = $student->whereIdClass($classroom->id);
        }

        $student = $student->orderBy('name')
            ->get();

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

        $cols = ['A', 'B', 'C', 'D', 'E'];
        $last_col = end($cols);
        $row = 1;

        $sheet->setCellValue('A' . $row, __('label.report_bill_total'));
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, __('label.level_education') . ' : ' . strtoupper($education));
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $row++;

        $sheet->setCellValue('A' . $row, __('label.level_class') . ' : ' . $class_name);
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $row++;

        if (!empty($classroom_name)) {
            $sheet->setCellValue('A' . $row, __('label.class') . ' : ' . $classroom_name);
            $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
            $row++;
        }

        $row++;

        // Table Header
        $sheet->setCellValue('A' . $row, __('label.no'));
        $sheet->setCellValue('B' . $row, __('label.nis'));
        $sheet->setCellValue('C' . $row, __('label.student_name'));
        $sheet->setCellValue('D' . $row, __('label.class'));
        $sheet->setCellValue('E' . $row, __('label.total'));

        foreach ($cols as $c)
            $sheet->getStyle($c . $row)->applyFromArray($style_col);

        $row++;

        // Table Body
        $no = 1;
        $total = 0;

        foreach ($student as $s) {
            $bill = TransactionBill::with(['bill' => fn($query) => $query->select('id')])
                ->whereIdStudent($s->id)
                ->whereHas('bill', function ($query) use ($year) {
                    $query->whereIdYear($year);
                })
                ->sum('total');

            $total += $bill;

            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $s->nis);
            $sheet->setCellValue('C' . $row, $s->name);
            $sheet->setCellValue('D' . $row, $s->class->name);
            $sheet->setCellValue('E' . $row, $bill);

            foreach ($cols as $c)
                $sheet->getStyle($c . $row)->applyFromArray($style_row);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            $row++;
            $no++;
        }

        $sheet->setCellValue('A' . $row, __('label.total'));
        $sheet->setCellValue('E' . $row, $total);
        $sheet->mergeCells('A' . $row . ':D' . $row);

        foreach ($cols as $c)
            $sheet->getStyle($c . $row)->applyFromArray($style_col);

        $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        // Setting
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(50);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->setTitle(__('label.report_bill_total'));

        // Output
        $filename = str_replace(' ', '-', strtolower(__('label.report_bill_total'))) . '-' . date('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    public function downloadExcelOutstandingArrears(Request $request)
    {
        $tmp = TempFile::whereId(Crypt::decrypt($request->tmp))->firstOrFail();
        $tmp_file = Storage::get('tmp/' . $tmp->file);
        $data = json_decode($tmp_file);

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

        $columns = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
        ];

        $cols = ['A', 'B', 'C', 'D'];
        $cols_count = count($cols);
        $cols_count_end = $cols_count + $data->count;

        for ($c=$cols_count; $c<$cols_count_end; $c++)
            array_push($cols, $columns[$c]);

        $last_col = end($cols);
        $row = 1;

        $sheet->setCellValue('A' . $row, __('label.report_outstanding_arrears'));
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 's/d ' . __('label.month') . ' : ' . Common::monthFormat($request->month) . ' ' . $request->year);
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $row++;

        if (!empty($request->education)) {
            $sheet->setCellValue('A' . $row, __('label.level_education') . ' : ' . strtoupper($request->education));
            $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
            $row++;
        }

        if (!empty($request->class)) {
            $class = Classroom::select('name')->whereId($request->class)->first();
            $sheet->setCellValue('A' . $row, __('label.level_class') . ' : ' . $class->name);
            $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
            $row++;
        }

        $row++;

        // Table Header
        $sheet->setCellValue('A' . $row, __('label.no'));
        $sheet->setCellValue('B' . $row, __('label.nis'));
        $sheet->setCellValue('C' . $row, __('label.name'));
        $sheet->setCellValue('D' . $row, __('label.arrears_total'));

        $no = 1;

        for ($c = $cols_count; $c < $cols_count_end; $c++) {
            $sheet->setCellValue($cols[$c] . $row, __('label.arrears') . ' ' . $no);
            $no++;
        }

        foreach ($cols as $c)
            $sheet->getStyle($c . $row)->applyFromArray($style_col);

        $row++;

        // Table Body
        $no = 1;

        foreach ($data->bills as $b) {
            if (empty(@$b->nis))
                continue;

            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValueExplicit('B' . $row, $b->nis, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, $b->name);
            $sheet->setCellValue('D' . $row, $b->total);

            $col_arrears = $cols_count;

            foreach ($b->bills as $bi) {
                $sheet->setCellValue($cols[$col_arrears] . $row, $bi->name . ' - Rp. ' . number_format($bi->total, 0, '', '.'));
                $col_arrears++;
            }

            foreach ($cols as $c)
                $sheet->getStyle($c . $row)->applyFromArray($style_row);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            $no++;
            $row++;
        }

        // Setting
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(20);

        for ($c = $cols_count; $c < $cols_count_end; $c++)
            $sheet->getColumnDimension($cols[$c])->setWidth(40);

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->setTitle(__('label.report_outstanding_arrears'));

        // Output
        $filename = str_replace(' ', '-', strtolower(__('label.report_outstanding_arrears'))) . '-' . date('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    public function downloadExcelOngoingCollectionSpp(Request $request)
    {
        $tmp = TempFile::whereId(Crypt::decrypt($request->tmp))->firstOrFail();
        $tmp_file = Storage::get('tmp/' . $tmp->file);
        $data = json_decode($tmp_file);

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

        $columns = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
        ];

        $cols = ['A', 'B', 'C', 'D'];
        $cols_count = count($cols);
        $cols_count_end = $cols_count + $data->count;

        for ($c=$cols_count; $c<$cols_count_end; $c++)
            array_push($cols, $columns[$c]);

        $last_col = end($cols);
        $row = 1;

        $sheet->setCellValue('A' . $row, __('label.report_ongoing_collection_spp'));
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 's/d ' . __('label.month') . ' : ' . Common::monthFormat($request->month) . ' ' . $request->year);
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $row++;

        if (!empty($request->education)) {
            $sheet->setCellValue('A' . $row, __('label.level_education') . ' : ' . strtoupper($request->education));
            $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
            $row++;
        }

        if (!empty($request->class)) {
            $class = Classroom::select('name')->whereId($request->class)->first();
            $sheet->setCellValue('A' . $row, __('label.level_class') . ' : ' . $class->name);
            $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
            $row++;
        }

        $row++;

        // Table Header
        $sheet->setCellValue('A' . $row, __('label.no'));
        $sheet->setCellValue('B' . $row, __('label.nis'));
        $sheet->setCellValue('C' . $row, __('label.name'));
        $sheet->setCellValue('D' . $row, __('label.spp_total'));

        $no = 1;

        for ($c = $cols_count; $c < $cols_count_end; $c++) {
            $sheet->setCellValue($cols[$c] . $row, __('label.spp') . ' ' . $no);
            $no++;
        }

        foreach ($cols as $c)
            $sheet->getStyle($c . $row)->applyFromArray($style_col);

        $row++;

        // Table Body
        $no = 1;

        foreach ($data->bills as $b) {
            if (empty(@$b->nis))
                continue;

            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $b->nis);
            $sheet->setCellValue('C' . $row, $b->name);
            $sheet->setCellValue('D' . $row, $b->total);

            $col_arrears = $cols_count;

            foreach ($b->bills as $bi) {
                $sheet->setCellValue($cols[$col_arrears] . $row, $bi->name . ' - Rp. ' . number_format($bi->total, 0, '', '.'));
                $col_arrears++;
            }

            foreach ($cols as $c)
                $sheet->getStyle($c . $row)->applyFromArray($style_row);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            $no++;
            $row++;
        }

        // Setting
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(20);

        for ($c = $cols_count; $c < $cols_count_end; $c++)
            $sheet->getColumnDimension($cols[$c])->setWidth(40);

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->setTitle(__('label.report_ongoing_collection_spp'));

        // Output
        $filename = str_replace(' ', '-', strtolower(__('label.report_ongoing_collection_spp'))) . '-' . date('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    public function downloadExcelPaymentMethod(Request $request)
    {
        $filter_start = (empty($request->start_date)) ? date('Y-m') . '-01' : $request->start_date;
        $filter_end = (empty($request->end_date)) ? date('Y-m-t') : $request->end_date;

        if ($filter_start == $filter_end) {
            $rr_cash = ReportBillMethod::select('total')->cash()->whereDates($filter_start)->first();
            $rr_bni = ReportBillMethod::select('total')->bni()->whereDates($filter_start)->first();
            $rr_bsi = ReportBillMethod::select('total')->bsi()->whereDates($filter_start)->first();
            $rr_cash = ReportBillMethod::select('total')->topupBalance()->whereDates($filter_start)->first();

            $cash = (empty($rr_cash)) ? 0 : $rr_cash->total;
            $bni = (empty($rr_bni)) ? 0 : $rr_bni->total;
            $bsi = (empty($rr_bsi)) ? 0 : $rr_cash->total;
            $topup = (empty($rr_topup)) ? 0 : $rr_topup->total;
        } else {
            $cash = ReportBillMethod::cash()
                ->where(function ($query) use ($filter_start, $filter_end) {
                    $query->whereBetween('dates', [$filter_start, $filter_end]);
                })
                ->sum('total');

            $bni = ReportBillMethod::bni()
                ->where(function ($query) use ($filter_start, $filter_end) {
                    $query->whereBetween('dates', [$filter_start, $filter_end]);
                })
                ->sum('total');

            $bsi = ReportBillMethod::bsi()
                ->where(function ($query) use ($filter_start, $filter_end) {
                    $query->whereBetween('dates', [$filter_start, $filter_end]);
                })
                ->sum('total');

            $topup = ReportBillMethod::topupBalance()
                ->where(function ($query) use ($filter_start, $filter_end) {
                    $query->whereBetween('dates', [$filter_start, $filter_end]);
                })
                ->sum('total');
        }

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

        $cols = ['A', 'B', 'C'];
        $last_col = end($cols);
        $row = 1;

        $sheet->setCellValue('A' . $row, __('label.report_payment_method'));
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $date = ($filter_start == $filter_end) ? Common::dateFormat($filter_start) : Common::dateFormat($filter_start) . ' - ' . Common::dateFormat($filter_end);
        $sheet->setCellValue('A' . $row, __('label.date') . ' : ' . $date);
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $row += 2;

        // Table Header
        $sheet->setCellValue('A' . $row, __('label.no'));
        $sheet->setCellValue('B' . $row, __('label.payment_method'));
        $sheet->setCellValue('C' . $row, __('label.total'));

        foreach ($cols as $c)
            $sheet->getStyle($c . $row)->applyFromArray($style_col);

        $row++;

        // Table Body
        $no = 1;
        $method = [
            ['name' => __('label.cash'), 'total' => $cash],
            ['name' => __('label.bank_bni'), 'total' => $bni],
            ['name' => __('label.bank_bsi'), 'total' => $bsi],
            ['name' => __('label.balance_topup'), 'total' => $topup],
        ];

        foreach ($method as $m) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $m['name']);
            $sheet->setCellValue('C' . $row, $m['total']);

            foreach ($cols as $c)
                $sheet->getStyle($c . $row)->applyFromArray($style_row);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            $row++;
            $no++;
        }

        $total = $cash + $bni + $bsi + $topup;
        $sheet->setCellValue('A' . $row, __('label.total'));
        $sheet->setCellValue('C' . $row, $total);

        foreach ($cols as $c)
            $sheet->getStyle($c . $row)->applyFromArray($style_col);

        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        // Setting
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(20);

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->setTitle(__('label.report_payment_method'));

        // Output
        $filename = str_replace(' ', '-', strtolower(__('label.report_payment_method'))) . '-' . date('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    public function downloadExcelDonation(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $donatur_name = '';

        if (!empty($request->donatur)) {
            $donatur = Donation::select('name')->whereId($request->donatur)->first();
            $donatur_name = $donatur->name;
        }

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

        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $last_col = end($cols);
        $row = 1;

        $sheet->setCellValue('A' . $row, __('label.report_donation'));
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, __('label.date') . ' : ' . Common::dateFormat($start_date, 'dd mmm yyyy') . ' - ' . Common::dateFormat($end_date, 'dd mmm yyyy'));
        $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
        $row++;

        if (!empty($request->donatur)) {
            $sheet->setCellValue('A' . $row, __('label.donatur_name') . ' : ' . $donatur_name);
            $sheet->mergeCells('A' . $row . ':' . $last_col . $row);
            $row += 2;
        } else
            $row++;

        // Table Header
        $sheet->setCellValue('A' . $row, __('label.no'));
        $sheet->setCellValue('B' . $row, __('label.donatur_name'));
        $sheet->setCellValue('C' . $row, __('label.nis'));
        $sheet->setCellValue('D' . $row, __('label.student_name'));
        $sheet->setCellValue('E' . $row, __('label.transaction_number'));
        $sheet->setCellValue('F' . $row, __('label.payment_date'));
        $sheet->setCellValue('G' . $row, __('label.nominal'));

        foreach ($cols as $c)
            $sheet->getStyle($c . $row)->applyFromArray($style_col);

        $row++;

        // Table Body
        $no = 1;
        $donation = DonationHistory::select('id', 'id_donation', 'id_student', 'id_transaction', 'nominal', 'paid_at')
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'id_class', 'nis', 'name')->with(['class' => fn($qc) => $qc->select('id', 'name')]);
                },
                'donation' => fn($query) => $query->select('id', 'name'),
                'transaction' => fn($query) => $query->select('id', 'number'),
            ])
            ->where(function ($query) use ($start_date, $end_date) {
                $query->whereBetween('paid_at', [$start_date, $end_date]);
            });

        if (!empty($request->donatur)) {
            $donation = $donation->whereIdDonation($request->donatur);
        }

        $donation = $donation->orderBy('paid_at', 'desc')->get();

        foreach ($donation as $d) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $d->donation->name);
            $sheet->setCellValue('C' . $row, $d->student->nis);
            $sheet->setCellValue('D' . $row, $d->student->name);
            $sheet->setCellValue('E' . $row, $d->transaction->number);
            $sheet->setCellValue('F' . $row, Common::dateFormat($d->paid_at, 'dd mmmm yyyy, hh:ii WIB'));
            $sheet->setCellValue('G' . $row, $d->nominal);

            foreach ($cols as $c)
                $sheet->getStyle($c . $row)->applyFromArray($style_row);

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

            $row++;
            $no++;
        }

        // Setting
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(35);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(35);
        $sheet->getColumnDimension('G')->setWidth(20);

        $sheet->getDefaultRowDimension()->setRowHeight(20);
        $sheet->setTitle(__('label.report_donation'));

        // Output
        $filename = str_replace(' ', '-', strtolower(__('label.report_donation'))) . '-' . date('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    public function getTotalBill(Request $request)
    {
        $year = $request->year;
        $education = $request->education;
        $class = $request->class;
        $classroom = $request->classroom;

        $bill = TransactionBill::with([
                'student' => function ($query) {
                    $query->select('id', 'id_class')->with(['class' => fn($qc) => $qc->select('id')]);
                },
                'bill' => fn($query) => $query->select('id')
            ]);
        
        if (empty($classroom)) {
            $bill = $bill->whereHas('student', function($query) use($education, $class) {
                $query->whereHas('class', function($qc) use($education, $class) {
                    $qc->whereLevelEducation($education);

                    if (!empty($class))
                        $qc->whereLevelClass($class);
                });
            });
        } else {
            $bill = $bill->whereHas('student', function ($query) use ($classroom) {
                $query->whereIdClass($classroom);
            });
        }
        
        $bill = $bill->whereHas('bill', function($query) use($year) {
                $query->whereIdYear($year);
            })
            ->sum('total');

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'total' => $bill
            ]
        ];

        return response()->json($response);
    }

    public function getTotalBillNotPaid(Request $request)
    {
        $year = $request->year;
        $education = $request->education;
        $class = $request->class;

        $bill = TransactionBill::with([
                'student' => function ($query) {
                    $query->select('id', 'id_class')->with(['class' => fn($qc) => $qc->select('id')]);
                },
                'bill' => fn($query) => $query->select('id')
            ])
            ->notPaid()
            ->whereHas('student', function($query) use($education, $class) {
                $query->whereHas('class', function($qc) use($education, $class) {
                    $qc->whereLevelEducation($education);

                    if (!empty($class))
                        $qc->whereLevelClass($class);
                });
            })
            ->whereHas('bill', function($query) use($year) {
                $query->whereIdYear($year);
            })
            ->sum('total');

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'total' => $bill
            ]
        ];

        return response()->json($response);
    }

    public function getOutstandingArrears(Request $request)
    {
        $limit = 50;
        $page = $request->page;
        $offset = ($page - 1) * $limit;

        $school_year = $request->school_year;
        $month = $request->month;
        $year = $request->year;
        $education = $request->education;
        $class = $request->class;
        $beasiswa = $request->beasiswa;
        $date_end = date('t', strtotime($year . '-' . $month . '-01'));
        $date = $year . '-' . $month . '-' . Str::padLeft($date_end, 2, '0');

        if ($page == 1) {
            $bill_count = TransactionBill::with([
                    'student' => fn($query) => $query->with(['class']),
                    'bill',
                ])
                ->notPaid()
                ->whereHas('student', function ($query) use ($beasiswa) {
                    $query->whereBeasiswa($beasiswa)->active();
                })
                ->whereHas('bill', function ($query) use ($school_year) {
                    $query->whereIdYear($school_year);
                })
                ->where('due_date', '<=', $date);
        }

        $bill = TransactionBill::select('id', 'id_bill', 'id_student', 'months', 'years', 'total')
            ->with([
                'student' => function($query) {
                    $query->select('id', 'nis', 'name')->with(['class' => fn($qc) => $qc->select('id')]);
                },
                'bill' => function($query) {
                    $query->select('id', 'id_type', 'name')->with(['type' => fn($qt) => $qt->select('id', 'name', 'period')]);
                }
            ])
            ->notPaid()
            ->whereHas('bill', function ($query) use ($school_year) {
                $query->whereIdYear($school_year);
            })
            ->whereHas('student', function ($query) use ($beasiswa) {
                $query->whereBeasiswa($beasiswa)->active();
            })
            ->where('due_date', '<=', $date);

        if (empty($class)) {
            if (!empty($education)) {
                if ($page == 1) {
                    $bill_count = $bill_count->whereHas('student', function($query) use($education) {
                        $query->whereHas('class', function($qc) use($education) {
                            $qc->whereLevelEducation($education);
                        });
                    });
                }

                $bill = $bill->whereHas('student', function($query) use($education) {
                    $query->whereHas('class', function($qc) use($education) {
                        $qc->whereLevelEducation($education);
                    });
                });
            }
        } else {
            if ($page == 1) {
                $bill_count = $bill_count->whereHas('student', function ($query) use ($class) {
                    $query->whereIdClass($class);
                });
            }

            $bill = $bill->whereHas('student', function ($query) use ($class) {
                $query->whereIdClass($class);
            });
        }

        $bill = $bill->limit($limit)->offset($offset)->orderBy('due_date')->orderBy('id')->get();
        $page_end = 0;

        if ($page == 1) {
            $bill_count = $bill_count->count();
            $page_end = ceil($bill_count / $limit);
        }

        $bills = [];
        $period_monthly = BillPeriod::Monthly->value;
        $period_semester = BillPeriod::Semiannual->value;

        foreach ($bill as $b) {
            if (!array_key_exists($b->student->id, $bills)) {
                $bills[$b->student->id] = [
                    'id' => $b->student->id,
                    'nis' => $b->student->nis,
                    'name' => $b->student->name,
                    'bills' => []
                ];
            }

            $bill_name = $b->bill->name;

            if ($b->bill->type->period->value == $period_monthly)
                $bill_name .= ' - ' . Common::monthFormat($b->months) . ' ' . $b->years;
            else if ($b->bill->type->period->value == $period_semester)
                $bill_name .= ' - Semester ' . $b->semester;

            array_push($bills[$b->student->id]['bills'], [
                'name' => $bill_name,
                'total' => $b->total
            ]);
        }

        sort($bills);

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'student' => $bills,
                'limit' => $limit,
                'page_end' => $page_end
            ]
        ];

        echo json_encode($response);
    }

    public function getOngoingCollectionSpp(Request $request)
    {

        $limit = 50;
        $page = $request->page;
        $offset = ($page - 1) * $limit;

        $sch_year = null;
        $sch_year_date = null;

        $month = $request->month;
        $year = $request->year;

        $date_end = date('t', strtotime($year . '-' . $month . '-01'));
        $date = $year . '-' . $month . '-' . Str::padLeft($date_end, 2, '0');

        // validate search by tahun ajaran
        if (!empty($request->school_year)) {
            // if possible use cache here
            $sch_year = Year::select('start_year', 'start_month', 'end_year', 'end_month')->where('id', $request->school_year)->firstOrFail();
            // validate if request filter month and year not in school year range
            $responseFail= response()->json(['status' => false, 'code' => 'filter-school-year', 'message' => __('string.filter_school_year')], 400);

            if ($year < $sch_year->start_year) {
                return $responseFail;
            }

            if ($month < $sch_year->start_month && ($year < $sch_year->end_year || $year == $sch_year->start_year)) {
                return $responseFail;
            }

            if ($month > $sch_year->end_month && ($year > $sch_year->end_year || $year == $sch_year->end_year)) {
                return $responseFail;
            }

            $date_start = date('Y-m-01', strtotime($sch_year->start_year . '-' . $sch_year->start_month . '-01'));
            $sch_year_date = $sch_year->start_year . '-' . $sch_year->start_month . '-' . Str::padLeft($date_start, 2, '0');
        }

        $education = $request->education;
        $class = $request->class;

        if ($page == 1) {
            $bill_count = TransactionBill::with(['student' => fn($query) => $query->with(['class'])])
                ->when(!empty($request->school_year), function($q) use ($sch_year_date, $date) {
                    $q->whereBetween('due_date', [$sch_year_date, $date]);
                })
                ->when(empty($request->school_year), function($q) use ($date) {
                    $q->where('due_date', '<=', $date);
                })
                ->where('status', TransactionStatus::Paid->value);
        }

        $bill = TransactionBill::select('id', 'id_bill', 'id_student', 'months', 'years', 'total')
            ->with([
                'student' => function($query) {
                    $query->select('id', 'nis', 'name')->with(['class' => fn($qc) => $qc->select('id')]);
                },
                'bill' => function($query) {
                    $query->select('id', 'id_type', 'name')->with(['type' => fn($qt) => $qt->select('id', 'name', 'period')]);
                }
            ])
            ->whereHas('bill.type', function ($query) {
                $query->where('spp', BillType::Spp->value);
            })
            ->when(!empty($request->school_year), function($q) use ($sch_year_date, $date) {
                $q->whereBetween('due_date', [$sch_year_date, $date]);
            })
            ->when(empty($request->school_year), function($q) use ($date) {
                $q->where('due_date', '<=', $date);
            })
            ->where('status', TransactionStatus::Paid->value);



        if (empty($class)) {
            if (!empty($education)) {
                if ($page == 1) {
                    $bill_count = $bill_count->whereHas('student', function($query) use($education) {
                        $query->whereHas('class', function($qc) use($education) {
                            $qc->whereLevelEducation($education);
                        });
                    });
                }

                $bill = $bill->whereHas('student', function($query) use($education) {
                    $query->whereHas('class', function($qc) use($education) {
                        $qc->whereLevelEducation($education);
                    });
                });
            }
        } else {
            if ($page == 1) {
                $bill_count = $bill_count->whereHas('student', function ($query) use ($class) {
                    $query->whereIdClass($class);
                });
            }

            $bill = $bill->whereHas('student', function ($query) use ($class) {
                $query->whereIdClass($class);
            });
        }

        $bill = $bill->limit($limit)->offset($offset)->orderBy('due_date')->get();
        $page_end = 0;

        if ($page == 1) {
            $bill_count = $bill_count->count();
            $page_end = ceil($bill_count / $limit);
        }

        $bills = [];
        $period_monthly = BillPeriod::Monthly->value;
        $period_semester = BillPeriod::Semiannual->value;

        foreach ($bill as $b) {
            if (!array_key_exists($b->student->id, $bills)) {
                $bills[$b->student->id] = [
                    'id' => $b->student->id,
                    'nis' => $b->student->nis,
                    'name' => $b->student->name,
                    'bills' => []
                ];
            }

            $bill_name = $b->bill->name;

            if ($b->bill->type->period->value == $period_monthly)
                $bill_name .= ' - ' . Common::monthFormat($b->months) . ' ' . $b->years;
            else if ($b->bill->type->period->value == $period_semester)
                $bill_name .= ' - Semester ' . $b->semester;

            array_push($bills[$b->student->id]['bills'], [
                'name' => $bill_name,
                'total' => $b->total
            ]);
        }

        sort($bills);

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'student' => $bills,
                'limit' => $limit,
                'page_end' => $page_end
            ]
        ];

        echo json_encode($response);
    }

    public function getOptionBill(Request $request)
    {
        $options = '<option value=""></option>';
        $bill = Bill::select('id', 'name')->whereIdYear($request->year)->orderBy('name')->get();

        foreach ($bill as $b)
            $options .= '<option value="' . $b->id . '">' . $b->name . '</option>';

        return response()->json(['option' => $options]);
    }
}
