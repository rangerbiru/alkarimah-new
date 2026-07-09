<?php

namespace App\Http\Controllers;

use App\Constants\EducationLevel;
use App\Constants\Modules;
use App\Enums\BillPeriod;
use App\Enums\TransactionFlag;
use App\Enums\UserRole;
use App\Helpers\Common;
use App\Models\AllowedSubmissionEmployee;
use App\Models\AttendanceGroup;
use App\Models\AttendanceGroupDays;
use App\Models\AttendanceGroupMembers;
use App\Models\AttendanceLocation;
use App\Models\AttendanceReport;
use App\Models\AttendanceShifts;
use App\Models\Departments;
use App\Models\Employee;
use App\Models\LunchRequest;
use App\Models\ModuleRights;
use App\Models\Parents;
use App\Models\ReportBill;
use App\Models\ReportBillClass;
use App\Models\ReportBillMethod;
use App\Models\ReportBillType;
use App\Models\SavingsWithdrawal;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\TransactionBill;
use App\Models\Year;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DashboardController extends Controller
{
    private $title = 'Dashboard';

    private $icon = 'bx bxs-bar-chart-alt-2';

    private $path = 'backend.dashboard.';

    public function index()
    {
        $user = Auth::user();

        if ($user->role == UserRole::OrangTua) {
            // setcookie('paycode', rand(100, 999), time() + (86400 * 30), "/");
            // setcookie('paycode', '', time() - 3600, '/');
            return $this->orangTua();
        } elseif ($user->role == UserRole::PenanggungJawabTabungan) {
            return $this->penanggungJawabTabungan();
        } elseif ($user->role == UserRole::WaliKelas) {
            return $this->waliKelas();
        } elseif ($user->role == UserRole::Kasir) {
            return $this->kasir();
        } elseif ($user->role == UserRole::Pegawai) {
            return $this->pegawai();
        } else {
            return $this->admin();
        }
    }

    private function admin()
    {
        $today = Carbon::now()->format('Y-m-d');

        $attendance = AttendanceReport::with('employee', 'group')->whereDate('date', $today)->get();
        $hadir = $attendance->where('status', 'hadir');
        $izin = $attendance->where('status', 'izin');
        $sakit = $attendance->where('status', 'sakit');
        $alfa = $attendance->where('status', 'alpha');

        $totalHadirTerlambat = $hadir->filter(function ($item) {
            return ! empty($item->reason_in);
        });

        $totalEmployees = Employee::where('status', 1)
            ->whereIn('status_employment', [1, 2])
            ->count();

        $stayEmployees = Employee::where('status', 1)
            ->where(function ($query) {
                $query->where('status_employment', 1);
            })
            ->count();

        $honorerEmployees = Employee::where('status', 1)
            ->where(function ($query) {
                $query->where('status_employment', 2);
            })
            ->count();

        return view($this->path.'index', [
            'icon' => 'bx bxs-user-check',
            'title' => $this->title,
            'totalHadir' => $hadir->count(),
            'totalIzin' => $izin->count(),
            'totalSakit' => $sakit->count(),
            'totalAlfa' => $alfa->count(),
            'totalTidakHadir' => $izin->count() + $sakit->count() + $alfa->count(),
            'totalHadirTerlambat' => $totalHadirTerlambat->count(),
            'totalEmployees' => $totalEmployees,
            'attendance' => $attendance,
            'stayEmployees' => $stayEmployees,
            'honorerEmployees' => $honorerEmployees,
        ]);
    }

    public function datatableAttendance(Request $request)
    {
        $search = $request->input('search')['value'] ?? '';
        $limit = $request->input('length') ?? 10;
        $start = $request->input('start') ?? 0;

        $today = Carbon::now()->format('Y-m-d');

        $query = AttendanceReport::with('employee', 'group')
            ->whereDate('date', $today);

        if (! empty($search)) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $total = $query->count();

        $attendance = $query->offset($start)
            ->limit($limit)
            ->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $attendance,
        ]);
    }

    public function getPositions()
    {
        $positions = AttendanceGroup::with('position')->get();

        return response()->json($positions);
    }

    public function getData(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $positionId = $request->input('position_id');

        $start = Carbon::parse($month)->startOfMonth();
        $end = Carbon::parse($month)->endOfMonth();

        $dates = collect();
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dates->push($date->format('Y-m-d'));
        }

        if (empty($positionId)) {
            $employees = Employee::orderBy('name')->where('status', 1)->paginate(10);
            $attendanceData = AttendanceReport::whereBetween('date', [$start, $end])
                ->get()
                ->groupBy('employee_id');
        } else {
            $employeeIds = AttendanceGroupMembers::where('attendance_group_id', $positionId)
                ->pluck('employee_id');

            $employees = Employee::whereIn('id', $employeeIds)
                ->orderBy('name')
                ->paginate(10);

            $attendanceData = AttendanceReport::whereBetween('date', [$start, $end])
                ->whereIn('employee_id', $employeeIds)
                ->get()
                ->groupBy('employee_id');
        }

        $rows = $employees->map(function ($emp) use ($dates, $attendanceData) {
            $records = $attendanceData->get($emp->id, collect());
            $daily = [];

            foreach ($dates as $date) {
                $record = $records->firstWhere('date', $date);
                $daily[$date] = $record ? $record->status : '-';
            }

            return [
                'name' => $emp->name,
                'attendance' => $daily,
            ];
        });

        return response()->json([
            'dates' => $dates,
            'rows' => $rows,
            'pagination' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
            ],
        ]);
    }

    public function exportExcel(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $positionId = $request->input('position_id');

        $start = \Carbon\Carbon::parse($month)->startOfMonth();
        $end = \Carbon\Carbon::parse($month)->endOfMonth();

        $employees = Employee::orderBy('name')->where('status', 1)->get();
        $attendanceData = AttendanceReport::whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('employee_id');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Judul laporan
        $sheet->setCellValue('B1', 'Laporan Data Absensi Karyawan');
        $sheet->mergeCells('B1:AF1');
        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Periode: '.$start->translatedFormat('F Y'));
        $sheet->setCellValue('A3', 'Tanggal Export: '.now()->translatedFormat('l, d F Y H:i:s'));

        // Header tanggal dan kolom rekap
        $headerRow = 5;
        $sheet->setCellValue('A'.$headerRow, 'Nama');
        $col = 'B';
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $sheet->setCellValue($col.$headerRow, $date->format('d'));
            $col++;
        }

        // Kolom tambahan
        $jumlahHadirCol = $col;
        $totalJamCol = ++$col;
        $lateCountCol = ++$col;
        $totalLateMinutesCol = ++$col;
        $pulangAwalCountCol = ++$col;
        $totalPulangAwalMinutesCol = ++$col;
        $tidakAbsenPulangCol = ++$col;
        $tidakHadirCol = ++$col;

        // Header tambahan
        $sheet->setCellValue($jumlahHadirCol.$headerRow, 'Jumlah Hadir (hari)');
        $sheet->setCellValue($totalJamCol.$headerRow, 'Total Jam Hadir');
        $sheet->setCellValue($lateCountCol.$headerRow, 'Terlambat (kali)');
        $sheet->setCellValue($totalLateMinutesCol.$headerRow, 'Total Menit Terlambat');
        $sheet->setCellValue($pulangAwalCountCol.$headerRow, 'Pulang Awal (kali)');
        $sheet->setCellValue($totalPulangAwalMinutesCol.$headerRow, 'Total Menit Pulang Awal');
        $sheet->setCellValue($tidakAbsenPulangCol.$headerRow, 'Tidak Absen Pulang (kali)');
        $sheet->setCellValue($tidakHadirCol.$headerRow, 'Tidak Hadir (hari)');

        $row = $headerRow + 1;

        foreach ($employees as $emp) {
            $sheet->setCellValue('A'.$row, $emp->name);

            $col = 'B';
            $hadirCount = 0;
            $totalHours = 0;
            $lateCount = 0;
            $totalLateMinutes = 0;
            $pulangAwalCount = 0;
            $totalPulangAwalMinutes = 0;
            $tidakAbsenPulang = 0;
            $tidakHadir = 0;

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $record = $attendanceData->get($emp->id, collect())->firstWhere('date', $date->format('Y-m-d'));
                $status = $record ? ucfirst($record->status) : '-';

                if ($record) {
                    // Hadir
                    if (strtolower($status) === 'hadir') {
                        $hadirCount++;

                        // Hitung jam kerja
                        if (! empty($record->work_minutes)) {
                            $totalHours += $record->work_minutes / 60;

                            // Deteksi pulang awal
                            if (! empty($record->early_leave_minutes > 0 || $record->reason_out)) {
                                $pulangAwalCount++;
                                $totalPulangAwalMinutes += $record->early_leave_minutes;
                            }
                        }

                        // Deteksi terlambat
                        if (! empty($record->late_minutes > 0 || $record->reason_in)) {
                            $lateCount++;
                            $totalLateMinutes += $record->late_minutes;
                        }

                        // Tidak absen pulang
                        if (empty($record->check_out_time)) {
                            $tidakAbsenPulang++;
                        }
                    } else {
                        $tidakHadir++;
                    }
                } else {
                    $tidakHadir++;
                }

                $sheet->setCellValue($col.$row, $status);
                $col++;
            }

            // Rekap kolom tambahan
            $sheet->setCellValue($jumlahHadirCol.$row, $hadirCount);
            $sheet->setCellValue($totalJamCol.$row, floor($totalHours).' jam');
            $sheet->setCellValue($lateCountCol.$row, $lateCount);
            $sheet->setCellValue($totalLateMinutesCol.$row, $totalLateMinutes);
            $sheet->setCellValue($pulangAwalCountCol.$row, $pulangAwalCount);
            $sheet->setCellValue($totalPulangAwalMinutesCol.$row, $totalPulangAwalMinutes);
            $sheet->setCellValue($tidakAbsenPulangCol.$row, $tidakAbsenPulang);
            $sheet->setCellValue($tidakHadirCol.$row, $tidakHadir);

            $row++;
        }

        // Style header
        $sheet->freezePane('B'.($headerRow + 1));
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        $headerRange = 'A'.$headerRow.':'.$highestColumn.$headerRow;
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        // Simpan file
        $fileName = 'data_absensi_'.$month.'_'.now()->format('H-i-s').'.xlsx';
        $filePath = storage_path($fileName);
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    private function kasir()
    {
        $type_bill = TransactionFlag::Tagihan->value;
        $type_topup = TransactionFlag::TopupSaldo->value;
        $type_withdrawal = TransactionFlag::PengambilanTabungan->value;
        $years = Year::selectRaw('id, CONCAT("Thn. Ajaran : ", start_year, " - ", end_year) AS start_year')->orderBy('start_year', 'desc')->pluck('start_year', 'id');
        $year = Year::select('id')->active()->first();

        return view($this->path.'kasir', [
            'icon' => 'bx bxs-home-smile',
            'title' => $this->title,
            'type_bill' => $type_bill,
            'type_topup' => $type_topup,
            'type_withdrawal' => $type_withdrawal,
            'years' => $years,
            'year' => $year,
        ]);
    }

    private function orangTua()
    {
        $transactions = [];
        $transaction_count = 0;
        $period_monthly = BillPeriod::Monthly->value;
        $period_semester = BillPeriod::Semiannual->value;

        $transaction = Transaction::select('id', 'id_student', 'payment_method', 'paid_at', 'flag', 'total')
            ->with(['student' => fn ($query) => $query->select('id', 'id_parent', 'name')])
            ->whereHas('student', fn ($query) => $query->whereIdParent(Auth::user()->parent->id))
            ->paid()
            ->orderBy('paid_at', 'desc')
            ->get();

        foreach ($transaction as $t) {
            if ($transaction_count == 6) {
                break;
            }

            $method = $t->method->name;

            if ($t->flag->value == TransactionFlag::Tagihan->value) {
                $bills = TransactionBill::select('id', 'id_bill', 'semester', 'months', 'years', 'total')
                    ->with([
                        'bill' => function ($query) {
                            $query->select('id', 'id_type')->with(['type' => fn ($qt) => $qt->select('id', 'name', 'period')]);
                        },
                    ])
                    ->whereIdTransaction($t->id)
                    ->get();

                foreach ($bills as $b) {
                    if ($transaction_count == 6) {
                        break;
                    }

                    $name = $b->bill->type->name;

                    if ($b->bill->type->period->value == $period_monthly) {
                        $name .= ' - '.Common::monthFormat($b->months).' '.$b->years;
                    } elseif ($b->bill->type->period->value == $period_semester) {
                        $name .= ' - Semester '.$b->semester;
                    }

                    array_push($transactions, (object) [
                        'icon' => $t->flag_detail->icon,
                        'flag' => $t->flag_detail->name,
                        'name' => $name,
                        'total' => $b->total,
                        'total_class' => 'text-danger',
                        'paid_at' => $t->paid_at,
                        'method' => $method,
                    ]);

                    $transaction_count++;
                }
            } else {
                array_push($transactions, (object) [
                    'icon' => $t->flag_detail->icon,
                    'flag' => $t->flag_detail->type,
                    'name' => $t->flag_detail->name,
                    'total' => $t->total,
                    'total_class' => ($t->flag->value == TransactionFlag::PengambilanTabungan->value) ? 'text-danger' : 'text-success',
                    'paid_at' => $t->paid_at,
                    'method' => $method,
                ]);

                $transaction_count++;
            }
        }

        return view($this->path.'orang-tua', [
            'title' => $this->title,
            'transaction' => (object) $transactions,
        ]);
    }

    private function penanggungJawabTabungan()
    {
        $withdrawal = SavingsWithdrawal::selectRaw('COUNT(id) AS amount, SUM(total) AS total')->notProcessed()->first();

        return view($this->path.'penanggung-jawab-tabungan', [
            'title' => $this->title,
            'icon' => $this->icon,
            'withdrawal' => $withdrawal,
        ]);
    }

    private function waliKelas()
    {
        return view($this->path.'wali-kelas', [
            'title' => $this->title,
            'icon' => $this->icon,
        ]);
    }

    private function pegawai()
    {
        $module_rights = ModuleRights::select('id_module')
            ->whereIdUser(Auth::id())
            ->pluck('id_module', 'id_module')
            ->toArray();

        $module_absence = Modules::CreateAbsence;

        $user = Auth::user();

        if (! $user->employee) {
            return back()->with('error', 'Data pegawai tidak ditemukan untuk akun ini.');
        }

        $employee = AttendanceGroupMembers::where('employee_id', $user->employee->id)->first();

        $allowedSubmissionEmployeeIds = AllowedSubmissionEmployee::pluck('employee_id')->toArray();
        if ($employee) {
            $allowedSubmission = in_array($employee->employee_id, $allowedSubmissionEmployeeIds);
        }

        if (! $employee) {
            return view($this->path.'pegawai', [
                'title' => $this->title,
                'module_rights' => $module_rights,
                'module_absence' => $module_absence,
                'attendanceLocation' => null,
                'todaySchedule' => null,
                'today' => Carbon::now()->translatedFormat('l, d F Y'),
                'attendance' => null,
                'errorMessage' => 'Anda belum terdaftar dalam grup absensi.',
                'employee' => $employee,
                'selectedShift' => null,
                'groupAttendance' => null,
                'shifts' => [],
                'allowedSubmission' => null,
            ]);
        }

        $attendanceLocation = AttendanceLocation::where('attendance_group_id', $employee->attendance_group_id)->first();

        $today = strtolower(Carbon::now()->translatedFormat('l'));
        $todayDate = Carbon::now()->format('Y-m-d');

        $todaySchedule = AttendanceGroupDays::where('attendance_group_id', $employee->attendance_group_id)
            ->where('day_name', $today)
            ->first();

        $attendance = AttendanceReport::where('employee_id', $employee->employee_id)
            ->whereDate('date', $todayDate)
            ->orderBy('id', 'desc')
            ->first();

        $selectedShift = $attendance ? $attendance->shift : null;

        $groupAttendance = AttendanceGroup::find($employee->attendance_group_id);

        $shifts = AttendanceShifts::where('attendance_group_id', $employee->attendance_group_id)
            ->where('day_name', $today)
            ->orderBy('id')
            ->first();

        $lunchReq = LunchRequest::where('employee_id', $employee->employee_id)->whereDate('created_at', $todayDate)->first();

        $totalUnreadSubmission = Auth::user()
            ->employee->unreadNotifications()
            ->where('data->type', 'submission')
            ->count();

        $totalUnreadPermit = Auth::user()
            ->employee->unreadNotifications()
            ->where('data->type', 'permit')
            ->count();

        $totalUnreadIndividualActivity = Auth::user()
            ->employee->unreadNotifications()
            ->where('data->type', 'individual_activity')
            ->count();

        $isLogistik = AllowedSubmissionEmployee::where('employee_id', $user->employee->id)->where('position', 'like', '%logistik%')->exists();

        $inDepartment = Departments::where('employee_id', $user->employee->id)->exists();

        $isPimpinan = AttendanceGroupMembers::with('attendanceGroup')
            ->where('employee_id', $user->employee->id)
            ->whereHas('attendanceGroup', function ($query) {
                $query->where('position', 10);
            })->exists();

        $isMudir = AllowedSubmissionEmployee::where('employee_id', $user->employee->id)->where('position', 'like', '%mudir%')->exists();
        $isWadir = AllowedSubmissionEmployee::where('employee_id', $user->employee->id)->where('position', 'like', '%wadir%')->exists();

        return view($this->path.'pegawai', [
            'title' => $this->title,
            'module_rights' => $module_rights,
            'module_absence' => $module_absence,
            'attendanceLocation' => $attendanceLocation,
            'todaySchedule' => $todaySchedule,
            'today' => Carbon::now()->translatedFormat('l, d F Y'),
            'attendance' => $attendance,
            'errorMessage' => null,
            'employee' => $employee,
            'groupAttendance' => $groupAttendance->shift_work,
            'selectedShift' => $selectedShift,
            'shifts' => $shifts,
            'allowedSubmission' => $allowedSubmission,
            'lunchReq' => $lunchReq,
            'totalUnreadSubmission' => $totalUnreadSubmission,
            'totalUnreadPermit' => $totalUnreadPermit,
            'totalUnreadIndividualActivity' => $totalUnreadIndividualActivity,
            'isLogistik' => $isLogistik,
            'inDepartment' => $inDepartment,
            'isPimpinan' => $isPimpinan,
            'isMudir' => $isMudir,
            'isWadir' => $isWadir,
        ]);
    }

    public function datatableWithdrawal(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $withdrawal = SavingsWithdrawal::select('id', 'id_student', 'number', 'dates', 'total')
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'id_class', 'nis', 'name')
                        ->with(['class' => fn ($qc) => $qc->select('id', 'name', 'level_education')]);
                },
            ])
            ->notProcessed();

        $withdrawal_count = $withdrawal->count();

        if (empty($search)) {
            $withdrawal_filter = $withdrawal;
        } else {
            $withdrawal_filter = $withdrawal->where(function ($query) use ($search) {
                $query->where('number', 'like', '%'.$search.'%')
                    ->orWhere('dates', 'like', '%'.$search.'%')
                    ->orWhereHas('student', function ($qs) use ($search) {
                        $qs->where('nis', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%')
                            ->orWhereHas('class', function ($qc) use ($search) {
                                $qc->where('name', 'like', '%'.$search.'%')
                                    ->where('level_education', 'like', '%'.$search.'%');
                            });
                    });
            });
        }

        $withdrawal_count_filter = $withdrawal_filter->count();
        $withdrawal_data = $withdrawal_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $withdrawal_arr = [];

        foreach ($withdrawal_data as $t) {
            $push = $t->toArray();
            $push['encrypted_id'] = $t->encrypted_id;

            array_push($withdrawal_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $withdrawal_count,
            'recordsFiltered' => $withdrawal_count_filter,
            'data' => $withdrawal_arr,
        ]);
    }

    public function datatableBillNotPaid(Request $request)
    {
        $period_monthly = BillPeriod::Monthly->value;
        $period_semester = BillPeriod::Semiannual->value;

        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $bill = TransactionBill::select('id', 'id_student', 'id_bill', 'semester', 'months', 'years', 'total')
            ->with([
                'student' => function ($query) {
                    $query->select('id', 'id_class', 'nis', 'name')->with(['class' => fn ($qc) => $qc->select('id', 'name')]);
                },
                'bill' => function ($query) {
                    $query->select('id', 'id_year', 'id_type', 'name')
                        ->with([
                            'year' => fn ($qt) => $qt->select('id', 'start_year', 'end_year'),
                            'type' => fn ($qt) => $qt->select('id', 'name', 'period'),
                        ]);
                },
            ])
            ->notPaid()
            ->whereHas('student', fn ($query) => $query->whereIdClass(Auth::user()->class->id));

        $bill_count = $bill->count();
        $bill_filter = $bill->where(function ($query) use ($search) {
            $query->whereHas('bill', function ($qb) use ($search) {
                $qb->where('name', 'like', '%'.$search.'%');
            })
                ->orWhereHas('student', function ($qs) use ($search) {
                    $qs->where('nis', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%');
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

            if ($b->bill->type->period->value == $period_monthly) {
                $push['bill_name'] .= ' Bulan '.Common::monthFormat($b->months).' '.$b->years;
            } elseif ($b->bill->type->period->value == $period_semester) {
                $push['bill_name'] .= ' Semester '.$b->semester;
            }

            array_push($bill_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $bill_count,
            'recordsFiltered' => $bill_count_filter,
            'data' => $bill_arr,
        ]);
    }

    public function getCount()
    {
        $savings = Student::sum('balance_savings');
        $topup = Parents::sum('balance');
        $cash = Transaction::selectRaw('SUM(total-unique_code) AS total')->tagihan()->paid()->notDeposit()->first();
        $unique_code = Transaction::paid()->notDepositCode()->sum('unique_code');

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'savings' => $savings,
                'topup' => $topup,
                'cash' => (empty(@$cash->total)) ? 0 : $cash->total,
                'unique_code' => $unique_code,
            ],
        ];

        return response()->json($response);
    }

    public function getPaymentProgress(Request $request)
    {
        $report = ReportBill::selectRaw('level, SUM(total) AS total, SUM(paid) AS paid, SUM(remaining) AS remaining')
            ->whereIdYear($request->year)
            ->groupBy('level')
            ->get();

        $total = (object) ['bill' => 0, 'paid' => 0, 'remaining' => 0];

        foreach ($report as $r) {
            $total->bill += $r->total;
            $total->paid += $r->paid;
            $total->remaining += $r->remaining;
        }

        $progress = ($total->bill == 0) ? 0 : ($total->paid / $total->bill) * 100;

        if ($progress >= 90) {
            $progress_color = 'bg-success';
        } elseif ($progress >= 26) {
            $progress_color = 'bg-primary';
        } else {
            $progress_color = 'bg-danger';
        }

        $view = view($this->path.'get-payment-progress', [
            'report' => $report,
            'total' => $total,
            'progress' => $progress,
            'progress_color' => $progress_color,
        ])->render();

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'report' => $view,
                'total' => $total,
                'progress' => $progress,
            ],
        ];

        return response()->json($response);
    }

    public function getReceipt(Request $request)
    {
        $year = $request->year;
        $rr_cash = ReportBillMethod::select('total')->whereIdYear($year)->cash()->today()->first();
        $rr_bni = ReportBillMethod::select('total')->whereIdYear($year)->bni()->today()->first();
        $rr_bsi = ReportBillMethod::select('total')->whereIdYear($year)->bsi()->today()->first();
        $rr_topup = ReportBillMethod::select('total')->whereIdYear($year)->topupBalance()->today()->first();

        $receipt_recipient = (object) [
            'cash' => (empty($rr_cash)) ? 0 : $rr_cash->total,
            'bni' => (empty($rr_bni)) ? 0 : $rr_bni->total,
            'bsi' => (empty($rr_bsi)) ? 0 : $rr_bsi->total,
            'topup' => (empty($rr_topup)) ? 0 : $rr_topup->total,
        ];

        $receipt_class = ReportBillClass::select('id', 'id_class', 'total')
            ->with([
                'class' => function ($query) {
                    $query->select('id', 'id_wali_kelas', 'name')
                        ->with(['waliKelas' => fn ($qw) => $qw->select('id', 'name')]);
                },
            ])
            ->whereIdYear($year)
            ->today()
            ->get();

        $classes = EducationLevel::Classes;
        $receipt_type = [];

        foreach ($classes as $c) {
            $report_bill_type = ReportBillType::select('id', 'id_type', 'total')
                ->with(['type' => fn ($query) => $query->select('id', 'name')])
                ->whereIdYear($year)
                ->whereLevel($c)
                ->today()
                ->get();

            if ($report_bill_type->count() > 0) {
                if (! array_key_exists($c, $receipt_type)) {
                    $receipt_type[$c] = [];
                }

                foreach ($report_bill_type as $r) {
                    array_push($receipt_type[$c], (object) [
                        'type' => $r->type->name,
                        'total' => $r->total,
                    ]);
                }
            }
        }

        $view_recipient = view($this->path.'get-receipt-recipient', [
            'recipient' => $receipt_recipient,
        ])->render();

        $view_class = view($this->path.'get-receipt-class', [
            'class' => $receipt_class,
        ])->render();

        $view_type = view($this->path.'get-receipt-type', [
            'type' => $receipt_type,
        ])->render();

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'recipient' => $view_recipient,
                'class' => $view_class,
                'type' => $view_type,
            ],
        ];

        return response()->json($response);
    }
}
