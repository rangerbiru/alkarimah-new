<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AllowedSubmissionEmployee;
use App\Models\AttendanceGroup;
use App\Models\AttendanceGroupMembers;
use App\Models\AttendanceReport;
use App\Models\Departments;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceReportController extends Controller
{
    private $title = 'Laporan Absensi';
    private $icon = 'bx bxs-report';
    private $path = 'backend.employee.attendance-report.';
    public function index()
    {
        $user = Auth::user();

        if (!$user->employee) {
            return back()->with('error', 'Akun Anda tidak terhubung ke data pegawai.');
        }

        $employeeId = $user->employee->id;
        $today = Carbon::now()->format('Y-m-d');

        $isPimpinan = AttendanceGroupMembers::with('attendanceGroup')
            ->where('employee_id', $employeeId)
            ->whereHas('attendanceGroup', function ($query) {
                $query->where('position', 10);
            })->exists();

        $userPositionIds = Departments::where('employee_id', $employeeId)
            ->pluck('position_id')
            ->unique();

        if ($isPimpinan) {
            $attendance = AttendanceReport::with('employee', 'group')
                ->whereDate('date', $today)
                ->get();
        } else {
            if ($userPositionIds->isEmpty()) {
                $attendance = collect();
            } else {
                $employeeIdsInPositions = AttendanceGroupMembers::with('attendanceGroup')->whereHas('attendanceGroup', function ($query) use ($userPositionIds) {
                    $query->whereIn('position', $userPositionIds);
                })->pluck('employee_id');

                $attendance = AttendanceReport::with('employee', 'group')
                    ->whereDate('date', $today)
                    ->whereIn('employee_id', $employeeIdsInPositions)
                    ->get();
            }
        }

        // Hitung status
        $hadir = $attendance->where('status', 'hadir');
        $izin = $attendance->where('status', 'izin');
        $sakit = $attendance->where('status', 'sakit');
        $alfa = $attendance->where('status', 'alpha');

        $totalHadirTerlambat = $hadir->filter(fn($item) => !empty($item->reason_in))->count();

        // Statistik
        if ($isPimpinan) {
            $totalEmployees = Employee::where('status', 1)
                ->whereIn('status_employment', [1, 2])
                ->count();

            $stayEmployees = Employee::where('status', 1)
                ->where('status_employment', 1)
                ->count();

            $honorerEmployees = Employee::where('status', 1)
                ->where('status_employment', 2)
                ->count();
        } else {
            if ($userPositionIds->isEmpty()) {
                $totalEmployees = 0;
                $stayEmployees = 0;
                $honorerEmployees = 0;
            } else {

                $employeeIdsInPositions = AttendanceGroupMembers::with('attendanceGroup')->whereHas('attendanceGroup', function ($query) use ($userPositionIds) {
                    $query->whereIn('position', $userPositionIds);
                })->pluck('employee_id');

                $totalEmployees = Employee::where('status', 1)
                    ->whereIn('status_employment', [1, 2])
                    ->whereIn('id', $employeeIdsInPositions)
                    ->count();

                $stayEmployees = Employee::where('status', 1)
                    ->where('status_employment', 1)
                    ->whereIn('id', $employeeIdsInPositions)
                    ->count();

                $honorerEmployees = Employee::where('status', 1)
                    ->where('status_employment', 2)
                    ->whereIn('id', $employeeIdsInPositions)
                    ->count();
            }
        }

        return view($this->path . 'index', [
            'icon' => $this->icon,
            'title' => $this->title,
            'totalHadir' => $hadir->count(),
            'totalIzin' => $izin->count(),
            'totalSakit' => $sakit->count(),
            'totalAlfa' => $alfa->count(),
            'totalTidakHadir' => $izin->count() + $sakit->count() + $alfa->count(),
            'totalHadirTerlambat' => $totalHadirTerlambat,
            'totalEmployees' => $totalEmployees,
            'stayEmployees' => $stayEmployees,
            'honorerEmployees' => $honorerEmployees,
            'attendance' => $attendance,
            'isPimpinan' => $isPimpinan,
            'userPositionIds' => $userPositionIds,
        ]);
    }

    public function datatable(Request $request)
    {
        $user = Auth::user();

        if (!$user->employee) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        }

        $employeeId = $user->employee->id;
        $today = Carbon::now()->format('Y-m-d');

        $isPimpinan = AttendanceGroupMembers::with('attendanceGroup')
            ->where('employee_id', $employeeId)
            ->whereHas('attendanceGroup', function ($query) {
                $query->where('position', 10);
            })->exists();

        $search = $request->input('search')['value'] ?? '';
        $limit = $request->input('length') ?? 10;
        $start = $request->input('start') ?? 0;

        $userPositionIds = Departments::where('employee_id', $employeeId)
            ->pluck('position_id')
            ->unique();

        $query = AttendanceReport::with('employee', 'group')
            ->whereDate('date', $today);

        // Terapkan filter berdasarkan role user (sama seperti di index)
        if (!$isPimpinan) {
            // Ambil semua attendance_group_id yang dimiliki user
            $userGroups = AttendanceGroupMembers::with('attendanceGroup')->whereHas('attendanceGroup', function ($query) use ($userPositionIds) {
                $query->whereIn('position', $userPositionIds);
            })->pluck('attendance_group_id');

            if ($userGroups->isEmpty()) {
                // Jika user tidak punya group, kembalikan data kosong
                return response()->json([
                    'draw' => intval($request->input('draw')),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => []
                ]);
            }

            // Filter attendance berdasarkan attendance_group_id yang dimiliki user
            $query->whereIn('attendance_group_id', $userGroups);
        }
        // Jika pimpinan, tidak ada filter tambahan (lihat semua data)

        // Terapkan pencarian jika ada
        if (!empty($search)) {
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
            'data' => $attendance
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

        $user = Auth::user();

        if (!$user->employee) {
            return response()->json([
                'dates' => $dates,
                'rows' => collect(),
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                ],
            ]);
        }

        $employeeId = $user->employee->id;

        $isPimpinan = AttendanceGroupMembers::with('attendanceGroup')
            ->where('employee_id', $employeeId)
            ->whereHas('attendanceGroup', function ($query) {
                $query->where('position', 10);
            })->exists();

        $userGroupIds = AttendanceGroupMembers::where('employee_id', $employeeId)
            ->pluck('attendance_group_id')
            ->unique();

        if (!empty($positionId)) {
            $filterGroupIds = [$positionId];
        } elseif ($isPimpinan) {
            $filterGroupIds = null;
        } else {
            $filterGroupIds = $userGroupIds->isEmpty() ? null : $userGroupIds->toArray();
        }

        if ($filterGroupIds === null) {
            $employees = Employee::orderBy('name')->where('status', 1)->paginate(10);
            $attendanceData = AttendanceReport::whereBetween('date', [$start, $end])
                ->get()
                ->groupBy('employee_id');
        } else {
            $employeeIds = AttendanceGroupMembers::whereIn('attendance_group_id', $filterGroupIds)
                ->pluck('employee_id')
                ->unique();

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

        $user = Auth::user();

        if (!$user->employee) {
            // Handle error atau redirect
            return back()->with('error', 'Akun Anda tidak terhubung ke data pegawai.');
        }

        $employeeId = $user->employee->id;

        // Cek apakah user adalah pimpinan (mudir/wadir)
        $isPimpinan = AttendanceGroupMembers::with('attendanceGroup')
            ->where('employee_id', $employeeId)
            ->whereHas('attendanceGroup', function ($query) {
                $query->where('position', 10);
            })->exists();

        // Ambil semua attendance_group_id yang dimiliki user
        $userGroupIds = AttendanceGroupMembers::where('employee_id', $employeeId)
            ->pluck('attendance_group_id')
            ->unique();

        // Tentukan filter berdasarkan role dan input
        if (!empty($positionId)) {
            // Jika position_id dipilih, gunakan itu
            $filterGroupIds = [$positionId];
        } elseif ($isPimpinan) {
            $filterGroupIds = null;
        } else {
            $filterGroupIds = $userGroupIds->isEmpty() ? null : $userGroupIds->toArray();
        }

        if ($filterGroupIds === null) {
            $employees = Employee::orderBy('name')->where('status', 1)->get();
            $attendanceData = AttendanceReport::whereBetween('date', [$start, $end])
                ->get()
                ->groupBy('employee_id');
        } else {
            $employeeIds = AttendanceGroupMembers::whereIn('attendance_group_id', $filterGroupIds)
                ->pluck('employee_id')
                ->unique();

            $employees = Employee::whereIn('id', $employeeIds)
                ->orderBy('name')
                ->get();

            $attendanceData = AttendanceReport::whereBetween('date', [$start, $end])
                ->whereIn('employee_id', $employeeIds)
                ->get()
                ->groupBy('employee_id');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Judul laporan
        $sheet->setCellValue('B1', 'Laporan Data Absensi Karyawan');
        $sheet->mergeCells('B1:AF1');
        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Periode: ' . $start->translatedFormat('F Y'));
        $sheet->setCellValue('A3', 'Tanggal Export: ' . now()->translatedFormat('l, d F Y H:i:s'));

        // Tambahkan informasi filter group jika ada
        if ($filterGroupIds !== null && !empty($filterGroupIds)) {
            if (count($filterGroupIds) === 1) {
                $groupName = AttendanceGroup::find($filterGroupIds[0])?->group_name ?? 'Group ' . $filterGroupIds[0];
                $sheet->setCellValue('A4', 'Filter Group: ' . $groupName);
            } else {
                $sheet->setCellValue('A4', 'Filter Groups: ' . implode(', ', $filterGroupIds));
            }
        } else {
            $sheet->setCellValue('A4', 'Filter Group: Semua Jabatan');
        }

        // Header tanggal dan kolom rekap
        $headerRow = 6; // Naikkan karena ada baris info group
        $sheet->setCellValue('A' . $headerRow, 'Nama');
        $col = 'B';
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $sheet->setCellValue($col . $headerRow, $date->format('d'));
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
        $sheet->setCellValue($jumlahHadirCol . $headerRow, 'Jumlah Hadir (hari)');
        $sheet->setCellValue($totalJamCol . $headerRow, 'Total Jam Hadir');
        $sheet->setCellValue($lateCountCol . $headerRow, 'Terlambat (kali)');
        $sheet->setCellValue($totalLateMinutesCol . $headerRow, 'Total Menit Terlambat');
        $sheet->setCellValue($pulangAwalCountCol . $headerRow, 'Pulang Awal (kali)');
        $sheet->setCellValue($totalPulangAwalMinutesCol . $headerRow, 'Total Menit Pulang Awal');
        $sheet->setCellValue($tidakAbsenPulangCol . $headerRow, 'Tidak Absen Pulang (kali)');
        $sheet->setCellValue($tidakHadirCol . $headerRow, 'Tidak Hadir (hari)');

        $row = $headerRow + 1;

        foreach ($employees as $emp) {
            $sheet->setCellValue('A' . $row, $emp->name);

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
                        if (!empty($record->work_minutes)) {
                            $totalHours += $record->work_minutes / 60;

                            // Deteksi pulang awal
                            if (!empty($record->early_leave_minutes > 0 || $record->reason_out)) {
                                $pulangAwalCount++;
                                $totalPulangAwalMinutes += $record->early_leave_minutes;
                            }
                        }

                        // Deteksi terlambat
                        if (!empty($record->late_minutes > 0 || $record->reason_in)) {
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

                $sheet->setCellValue($col . $row, $status);
                $col++;
            }

            // Rekap kolom tambahan
            $sheet->setCellValue($jumlahHadirCol . $row, $hadirCount);
            $sheet->setCellValue($totalJamCol . $row, floor($totalHours) . ' jam');
            $sheet->setCellValue($lateCountCol . $row, $lateCount);
            $sheet->setCellValue($totalLateMinutesCol . $row, $totalLateMinutes);
            $sheet->setCellValue($pulangAwalCountCol . $row, $pulangAwalCount);
            $sheet->setCellValue($totalPulangAwalMinutesCol . $row, $totalPulangAwalMinutes);
            $sheet->setCellValue($tidakAbsenPulangCol . $row, $tidakAbsenPulang);
            $sheet->setCellValue($tidakHadirCol . $row, $tidakHadir);

            $row++;
        }

        // Style header
        $sheet->freezePane('B' . ($headerRow + 1));
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        $headerRange = 'A' . $headerRow . ':' . $highestColumn . $headerRow;
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        // Simpan file
        $fileName = 'data_absensi_' . $month . '_' . now()->format('H-i-s') . '.xlsx';
        $filePath = storage_path($fileName);
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
