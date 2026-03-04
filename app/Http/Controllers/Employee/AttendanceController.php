<?php

namespace App\Http\Controllers\Employee;

use App\Helpers\AttendanceHelper;
use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\AttendanceGroupDays;
use App\Models\AttendanceGroupMembers;
use App\Models\AttendanceLocation;
use App\Models\AttendanceReport;
use App\Models\AttendanceShifts;
use App\Models\LunchRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceController extends Controller
{
    private $path = 'backend.employee.attendance.';
    private $icon = 'bx bxs-user-check';

    public function index()
    {
        $count = AttendanceReport::where('employee_id', Auth::user()->employee->id)->count();

        $attendance = AttendanceReport::with('group', 'employee')->where('employee_id', Auth::user()->employee->id)->get();

        // Hitung masing-masing status
        $hadir = AttendanceReport::where('employee_id', Auth::user()->employee->id)->where('status', 'hadir')->count();
        $izin = AttendanceReport::where('employee_id', Auth::user()->employee->id)->where('status', 'izin')->count();
        $alfa = AttendanceReport::where('employee_id', Auth::user()->employee->id)->where('status', 'alfa')->count();

        // Hitung persentase
        $hadirPercent = $count ? round(($hadir / $count) * 100, 2) : 0;
        $izinPercent = $count ? round(($izin / $count) * 100, 2) : 0;
        $alfaPercent = $count ? round(($alfa / $count) * 100, 2) : 0;

        return view($this->path . 'index', [
            'title' => 'Absensi Kehadiran',
            'icon' => $this->icon,
            'count' => $count,
            'attendance' => $attendance,
            'hadir' => $hadir,
            'izin' => $izin,
            'alfa' => $alfa,
            'hadirPercent' => $hadirPercent,
            'izinPercent' => $izinPercent,
            'alfaPercent' => $alfaPercent
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? null;
        $limit  = $request->input('length') ?? 10;
        $start  = $request->input('start') ?? 0;

        $query = AttendanceReport::with('group', 'employee')->where('employee_id', Auth::user()->employee->id);

        $recordsTotal = $query->count();

        if (!empty($search)) {
            $query->where('employee.name', 'like', '%' . $search . '%');
        }

        $recordsFiltered = $query->count();

        $data = $query->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data
        ]);
    }

    public function attendanceIn(Request $request)
    {
        $employee = AttendanceGroupMembers::where('employee_id', Auth::user()->employee->id)->first();
        if (!$employee) {
            return back()->with('error', 'Data karyawan tidak ditemukan.');
        }

        $today = strtolower(Carbon::now()->translatedFormat('l'));
        $todayDate = Carbon::now()->format('Y-m-d');

        $attendanceLocation = AttendanceLocation::where('attendance_group_id', $employee->attendance_group_id)->first();
        $todaySchedule = AttendanceGroupDays::where('attendance_group_id', $attendanceLocation->attendance_group_id)
            ->where('day_name', $today)
            ->first();

        if (!$todaySchedule) {
            return back()->with('error', 'Tidak ada jadwal absen untuk hari ini.');
        }

        if ($employee->attendanceGroup->shift_work == 'Y') {

            $shiftKey = $request->shift_selected;

            $shiftDB = AttendanceShifts::where('attendance_group_id', $employee->attendance_group_id)
                ->where('day_name', $today)
                ->first();

            if (!$shiftDB) {
                return back()->with('error', 'Data shift tidak ditemukan.');
            }

            if ($shiftKey === 'pagi') {
                $checkIn = $shiftDB->shift1_check_in_time;
                $checkOut = $shiftDB->shift1_check_out_time;
            } elseif ($shiftKey === 'sore') {
                $checkIn = $shiftDB->shift2_check_in_time;
                $checkOut = $shiftDB->shift2_check_out_time;
            } elseif ($shiftKey === 'malam') {
                $checkIn = $shiftDB->shift3_check_in_time;
                $checkOut = $shiftDB->shift3_check_out_time;
            } else {
                return back()->with('error', 'Shift tidak valid.');
            }

            $checkInTime  = Carbon::parse($checkIn);
            $checkOutTime = Carbon::parse($checkOut);

            $toleranceIn = (int) $todaySchedule->tolerance_in ?? 0;

            $alreadyCheckedIn = AttendanceReport::where('employee_id', $employee->employee_id)
                ->where('date', $todayDate)
                ->where('shift', $shiftKey)
                ->exists();

            if ($alreadyCheckedIn) {
                return redirect()->back()->with('error', 'Anda sudah absen untuk shift ini hari ini.');
            }

            if ($shiftKey === 'malam' && $checkOutTime->lessThan($checkInTime)) {
                $checkOutTime->addDay();
            }
        } else {
            $checkInTime = Carbon::parse($todaySchedule->check_in_time);
            $checkOutTime = Carbon::parse($todaySchedule->check_out_time);
            $toleranceIn = (int) $todaySchedule->tolerance_in;
        }

        $now = Carbon::now();

        try {
            $photoPath = ImageHelper::saveCompressedBase64Image($request->photo, $employee->employee_id);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $startTime = $checkInTime->copy()->subMinutes($toleranceIn);
        if ($now->lessThan($startTime)) {
            return back()->with('error', 'Belum waktunya untuk absen masuk.');
        }

        $lateMinutes = 0;
        if ($now->greaterThan($checkInTime) && $now->lessThan($checkOutTime)) {
            $lateMinutes = $checkInTime->diffInMinutes($now);
        }

        if ($now->greaterThan($checkOutTime)) {
            return redirect()->back()->with('error', 'Anda tidak bisa masuk karena sudah waktu pulang');
        }


        $attendance = AttendanceReport::create([
            'attendance_group_id' => $employee->attendance_group_id,
            'employee_id' => $employee->employee_id,
            'day' => $today,
            'date' => $todayDate,
            'check_in_time' => Carbon::now()->format('H:i'),
            'status' => 'hadir',
            'photo_in' => $photoPath,
            'late_minutes' => $lateMinutes,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'shift' => $shiftKey ?? null,
        ]);

        if ($attendance) {
            LunchRequest::create([
                'attendance_id' => $attendance->id,
                'attendance_group_id' => $employee->attendance_group_id,
                'employee_id' => $employee->employee_id,
                'request' => $request->lunch_request_selected
            ]);
        }

        if ($lateMinutes > 0) {
            return redirect()->back()->with('ask_reason', [
                'title' => 'Anda terlambat masuk kerja',
                'text' => 'Berikan alasan kenapa anda terlambat masuk kerja',
                'type' => 'warning',
                'reason_type' => 'in',
                'success' => 'Alhamdulillah, semoga besok bisa absen di waktu yang tepat.'
            ]);
        }

        return back()->with('success', 'Absen masuk berhasil disimpan.');
    }


    public function attendanceOut(Request $request)
    {
        $employee = AttendanceGroupMembers::where('employee_id', Auth::user()->employee->id)->first();
        if (!$employee) {
            return back()->with('error', 'Data karyawan tidak ditemukan.');
        }

        $today      = strtolower(Carbon::now()->translatedFormat('l'));
        $todayDate  = Carbon::now()->format('Y-m-d');

        $attendance = AttendanceReport::where('employee_id', $employee->employee_id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$attendance || !$attendance->check_in_time) {
            return back()->with('error', 'Anda belum melakukan absen masuk.');
        }

        $attendanceLocation = AttendanceLocation::where('attendance_group_id', $employee->attendance_group_id)->first();

        if ($employee->attendanceGroup->shift_work === 'Y') {

            $shiftKey = $attendance->shift;

            $dbShift = AttendanceShifts::where('attendance_group_id', $attendanceLocation->attendance_group_id)
                ->where('day_name', $today)
                ->first();

            if (!$dbShift) {
                return back()->with('error', 'Shift untuk hari ini belum diatur.');
            }

            if ($shiftKey === 'pagi') {
                $checkOutTime = Carbon::parse($dbShift->shift1_check_out_time);
            } elseif ($shiftKey === 'sore') {
                $checkOutTime = Carbon::parse($dbShift->shift2_check_out_time);
            } else {
                $checkOutTime = Carbon::parse($dbShift->shift3_check_out_time);
            }

            $days = AttendanceGroupDays::where('attendance_group_id', $attendanceLocation->attendance_group_id)
                ->where('day_name', $today)
                ->first();

            $toleranceOut = $days->tolerance_out;
        } else {
            $todaySchedule = AttendanceGroupDays::where('attendance_group_id', $attendanceLocation->attendance_group_id)
                ->where('day_name', $today)
                ->first();

            $checkOutTime = Carbon::parse($todaySchedule->check_out_time);
            $toleranceOut = (int) $todaySchedule->tolerance_out;
        }

        $now = Carbon::now();

        try {
            $photoPath = ImageHelper::saveCompressedBase64Image($request->photo, $employee->employee_id);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $earlyLeaveMinutes = $now->lessThan($checkOutTime)
            ? $now->diffInMinutes($checkOutTime)
            : 0;

        $checkIn = Carbon::parse($attendance->check_in_time);
        $workMinutes = $checkIn->diffInMinutes($now);

        $endLimit = $checkOutTime->copy()->addMinutes($toleranceOut);

        if ($now->lessThan($checkOutTime)) {

            $attendance->update([
                'check_out_time'      => $now->format('H:i'),
                'early_leave_minutes' => $earlyLeaveMinutes,
                'photo_out'           => $photoPath,
                'work_minutes'        => $workMinutes,
                'updated_at'          => $now,
            ]);

            return redirect()->back()->with('ask_reason', [
                'title' => 'Belum waktunya pulang!',
                'text'  => 'Berikan alasan kenapa Anda pulang lebih cepat.',
                'type'  => 'warning',
                'reason_type' => 'out',
            ]);
        }

        if ($now->greaterThan($endLimit)) {

            $attendance->update([
                'check_out_time' => $now->format('H:i'),
                'photo_out'      => $photoPath,
                'work_minutes'   => $workMinutes,
                'updated_at'     => $now,
            ]);

            return redirect()->back()->with('ask_reason', [
                'title' => 'Anda terlambat pulang!',
                'text'  => 'Berikan alasan kenapa Anda pulang melewati batas waktu.',
                'type'  => 'warning',
                'reason_type' => 'out',
            ]);
        }

        $attendance->update([
            'check_out_time' => $now->format('H:i'),
            'photo_out'      => $photoPath,
            'work_minutes'   => $workMinutes,
            'updated_at'     => $now,
        ]);

        return back()->with('success', 'Absen pulang berhasil disimpan.');
    }


    public function storeReason(Request $request)
    {
        $employeeId = Auth::user()->employee->id;
        $todayDate = Carbon::now()->format('Y-m-d');
        $reasonType = $request->reason_type;

        $request->validate([
            'reason' => 'required|string|max:255',
            'reason_type' => 'required|in:in,out',
        ]);

        $attendance = AttendanceReport::where('employee_id', $employeeId)
            ->latest()
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Data absensi hari ini belum ditemukan. Tidak dapat menyimpan alasan.'
            ], 404);
        }

        if ($reasonType === 'in') {
            $attendance->reason_in = $request->reason;
        } elseif ($reasonType === 'out') {
            $attendance->reason_out = $request->reason;
        }

        $attendance->updated_at = now();
        $attendance->save();

        return response()->json([
            'success' => true,
            'message' => 'Alasan berhasil disimpan.'
        ]);
    }

    // public function getAttendance(Request $request)
    // {
    //     $employeeId = Auth::user()->employee->id;
    //     $date = $request->date;
    //     $attendance = AttendanceReport::where('employee_id', $employeeId)
    //         ->where('date', $date)
    //         ->first();
    //     return response()->json($attendance);
    // }

    public function exportExcel()
    {
        // Ambil data absensi bulan ini
        $attendances = AttendanceReport::with('employee')
            ->where('employee_id', Auth::user()->employee->id)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->orderBy('date', 'asc')
            ->get();

        // Buat spreadsheet baru
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Judul
        $sheet->setCellValue('A1', 'Laporan Absensi Bulan ' . now()->translatedFormat('F Y'));
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Header tabel
        $headers = ['No', 'Nama Pegawai', 'Tanggal', 'Status', 'Jam Masuk', 'Jam Keluar', 'Alasan Masuk', 'Alasan Keluar'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $sheet->getStyle($col . '3')->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Isi data
        $row = 4;
        foreach ($attendances as $i => $att) {
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $att->employee->name ?? '-');
            $sheet->setCellValue('C' . $row, Carbon::parse($att->date)->translatedFormat('d F Y'));
            $sheet->setCellValue('D' . $row, ucfirst($att->status));
            $sheet->setCellValue('E' . $row, $att->check_in_time ? Carbon::parse($att->check_in_time)->format('H:i') : '-');
            $sheet->setCellValue('F' . $row, $att->check_out_time ? Carbon::parse($att->check_out_time)->format('H:i') : '-');
            $sheet->setCellValue('G' . $row, $att->reason_in ?? '-');
            $sheet->setCellValue('H' . $row, $att->reason_out ?? '-');
            $row++;
        }

        // Tambahkan border sederhana
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];
        $sheet->getStyle('A3:H' . ($row - 1))->applyFromArray($styleArray);

        // Simpan ke output
        $filename = 'attendance_' . now()->format('Y_m_d H:i') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $temp_file = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($temp_file);

        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }

    public function exportPdf()
    {
        $attendances = AttendanceReport::with('employee')->where('employee_id', Auth::user()->employee->id)
            ->orderBy('date', 'desc')
            ->get();

        $pdf = Pdf::loadView('backend.employee.attendance.attendance_pdf', compact('attendances'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('attendance_' . now()->format('Y_m_d H:i') . '.pdf');
    }
}
