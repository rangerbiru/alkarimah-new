<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceGroup;
use App\Models\AttendanceGroupMembers;
use App\Models\Departments;
use App\Models\Employee;
use App\Models\EmployeeActivity;
use App\Models\IndividualActivity;
use App\Notifications\IndividualActivityNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ActivityReportsController extends Controller
{
    private $title = 'label.individual_activity';
    private $path = 'backend.employee.activity-report.';
    private $icon = 'bx bx-book-reader';

    public function index()
    {
        $employee = Auth::user()->employee;

        $isHeadDepartment = Departments::where('employee_id', $employee->id)->exists();

        $activity = IndividualActivity::with('employee', 'activity')
            ->where('id_employee', $employee->id)
            ->get();

        if (!$isHeadDepartment) {
            DB::table('notifications')
                ->where('notifiable_id', $employee->id)
                ->update(['read_at' => now()]);
        }

        $isPimpinan = AttendanceGroupMembers::with('attendanceGroup')
            ->where('employee_id', $employee->id)
            ->whereHas('attendanceGroup', function ($query) {
                $query->where('position', 10);
            })->exists();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'activity' => $activity,
            'isHeadDepartment' => $isHeadDepartment,
            'isPimpinan' => $isPimpinan,
            'employee' => $employee
        ]);
    }

    public function create()
    {
        $idEmployee = Auth::user()->employee->id;

        $group = AttendanceGroupMembers::select('id', 'attendance_group_id')->where('employee_id', $idEmployee)->first();

        $groupEmployee = AttendanceGroup::where('id', $group->attendance_group_id)->first();

        if ($groupEmployee) {
            $nameActivity = EmployeeActivity::select('id', 'activity_name')->where('id_position', $groupEmployee->position)->get();
        }

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'idEmployee' => $idEmployee,
            'nameActivity' => $nameActivity
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_activity' => 'required|string',
            'description' => 'required|string',
            'photo'       => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'id_employee' => 'required|exists:employee,id',
        ], [
            'id_activity.required' => 'Nama aktivitas harus diisi.',
            'description.required' => 'Deskripsi aktivitas harus diisi.',
            'photo.file'           => 'Foto harus berupa file.',
            'photo.mimes'          => 'Format foto harus berupa jpg, jpeg, atau png.',
            'photo.max'            => 'Ukuran foto maksimal 5 MB.',
        ]);

        // Simpan file foto
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $filename = now()->format('Ymd_His') . '.' . $request->file('photo')->getClientOriginalExtension();
            $request->file('photo')->storeAs('activity-report', $filename, 'public');
            $photoPath = $filename;
        }

        // Buat laporan
        $activityReport = IndividualActivity::create([
            'id_activity' => $request->id_activity,
            'description' => $request->description,
            'photo'       => $photoPath,
            'id_employee' => $request->id_employee,
        ]);

        $this->sendActivityReportNotification($activityReport);

        return redirect()->route('employee.activity-report.index')
            ->with('success', 'Laporan aktivitas berhasil disimpan.');
    }

    private function sendActivityReportNotification($report)
    {
        $employee = Employee::find($report->id_employee);
        if (!$employee || !$employee->user) {
            return;
        }

        // Cari posisi pegawai melalui AttendanceGroup
        $groupMember = AttendanceGroupMembers::where('employee_id', $employee->id)->first();
        if (!$groupMember) return;

        $attendanceGroup = AttendanceGroup::find($groupMember->attendance_group_id);
        if (!$attendanceGroup) return;

        $positionId = $attendanceGroup->position;

        // Cari kepala departemen berdasarkan posisi
        $headEmployeeId = Departments::where('position_id', $positionId)->value('employee_id');
        if (!$headEmployeeId) return;

        $headEmployee = Employee::find($headEmployeeId);
        if (!$headEmployee) return;

        // Jangan kirim notifikasi ke diri sendiri (jika pegawai juga kepala)
        if ($headEmployee->id === $employee->id) {
            return;
        }

        // Kirim notifikasi
        $headEmployee->notify(new IndividualActivityNotification(
            $report->id,
            $employee->name,
            'individual_activity',
            $report->activity?->activity_name ?? 'Aktivitas',
            'pending'
        ));
    }

    public function edit($id)
    {
        $activity = IndividualActivity::findOrFail($id);
        $idEmployee = Auth::user()->employee->id;

        $group = AttendanceGroupMembers::select('id', 'attendance_group_id')->where('employee_id', $idEmployee)->first();

        $groupEmployee = AttendanceGroup::where('id', $group->attendance_group_id)->first();

        if ($groupEmployee) {
            $nameActivity = EmployeeActivity::select('id', 'activity_name')->where('id_position', $groupEmployee->position)->get();
        }

        return view($this->path . 'edit', [
            'title' => __('Edit Laporan Aktivitas'),
            'icon' => 'bx bxs-edit',
            'activity' => $activity,
            'idEmployee' => $idEmployee,
            'existingImage' => $activity->photo,
            'nameActivity' => $nameActivity
        ]);
    }

    /**
     * Update data
     */
    public function update(Request $request, $id)
    {
        $activity = IndividualActivity::findOrFail($id);

        $request->validate([
            'id_activity'        => 'required|string',
            'description' => 'required|string',
            'photo'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'id_employee' => 'required|integer'
        ], [
            'id_activity.required'        => 'Nama aktivitas harus diisi.',
            'description.required' => 'Deskripsi aktivitas harus diisi.',
            'photo.image'          => 'Foto harus berupa gambar.',
            'photo.mimes'          => 'Foto harus berformat: jpeg, png, jpg, atau gif.',
            'photo.max'            => 'Ukuran foto maksimal 2 MB.',
        ]);

        $data = [
            'id_activity'        => $request->id_activity,
            'description' => $request->description,
            'id_employee' => $request->id_employee,
        ];

        // Jika ada foto baru
        if ($request->hasFile('photo')) {
            // Hapus foto lama jika ada
            if ($activity->photo) {
                Storage::disk('public')->delete('activity-report/' . $activity->photo);
            }

            $file = $request->file('photo');
            $filename = now()->format('Ymd_His') . '.' . $file->getClientOriginalExtension();
            $file->storeAs('activity-report', $filename, 'public');
            $data['photo'] = $filename;
        }
        // Jika tidak upload foto baru, biarkan foto lama tetap (tidak diubah)

        $activity->update($data);

        return redirect()->route('employee.activity-report.index')
            ->with('success', 'Laporan aktivitas berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $activity = IndividualActivity::findOrFail($id);

        if ($activity->photo) {
            Storage::disk('public')->delete('activity-report/' . $activity->photo);
        }

        $activity->delete();

        return redirect()->route('employee.activity-report.index')
            ->with('success', 'Laporan aktivitas berhasil dihapus.');
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? null;
        $limit  = $request->input('length') ?? 10;
        $start  = $request->input('start') ?? 0;

        $employee = Auth::user()->employee;

        $groupMember = AttendanceGroupMembers::where('employee_id', $employee->id)->first();
        if (!$groupMember) {
            return $this->emptyResponse($request);
        }

        $attendanceGroup = AttendanceGroup::find($groupMember->attendance_group_id);
        if (!$attendanceGroup) {
            return $this->emptyResponse($request);
        }

        $userPositionId = $attendanceGroup->position;

        $headEmployeeId = Departments::where('position_id', $userPositionId)->value('employee_id');

        $allowedPositionIds = [];

        $managedPositionIds = Departments::where('employee_id', $employee->id)
            ->pluck('position_id')
            ->toArray();

        if (!empty($managedPositionIds)) {
            $allowedPositionIds = $managedPositionIds;
        } elseif ($headEmployeeId) {
            $allowedPositionIds = Departments::where('employee_id', $headEmployeeId)
                ->pluck('position_id')
                ->toArray();
        } else {
            $allowedPositionIds = [$userPositionId];
        }

        //  Query data
        $query = IndividualActivity::with('employee', 'activity')
            ->whereHas('activity', function ($q) use ($allowedPositionIds) {
                $q->whereIn('id_position', $allowedPositionIds);
            });

        $recordsTotal = $query->count();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('activity', function ($subQ) use ($search) {
                        $subQ->where('activity_name', 'like', "%{$search}%");
                    });
            });
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

    private function emptyResponse($request)
    {
        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => []
        ]);
    }

    public function storeComment(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $activity = IndividualActivity::findOrFail($id);

        if (!$request->user()->employee) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $activity->update([
            'comment' => $request->comment,
            'comment_by' => $request->user()->employee->id,
        ]);

        if ($activity->employee) {
            $activity->employee->notify(new IndividualActivityNotification(
                $activity->id,
                $activity->employee->name,
                'individual_activity',
                $activity->activity?->activity_name ?? 'Aktivitas',
                'response'
            ));
        }

        DB::table('notifications')
            ->where('data->report_id', (string) $activity->id)
            ->where('data->type', 'individual_activity')
            ->where('data->status', 'pending')
            ->update([
                'data->status' => 'telah dikomentari',
                'read_at' => now(),
            ]);

        return response()->json(['message' => 'Komentar berhasil disimpan.']);
    }

    public function exportExcel(Request $request)
    {
        $periode = $request->periode ?? now()->format('Y-m');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $row = 1;

        // =============================
        // JUDUL
        // =============================
        $sheet->setCellValue('A' . $row, 'REKAP Jumlah Kegiatan per Guru');
        $sheet->mergeCells('A' . $row . ':Z' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row += 2;

        $sheet->setCellValue('A' . $row, 'Periode: ' . $periode);
        $row += 2;

        // =============================
        // AMBIL SEMUA POSISI
        // =============================
        $positions = EmployeeActivity::select('id_position')
            ->distinct()
            ->pluck('id_position');

        foreach ($positions as $positionId) {

            // =============================
            // AMBIL ACTIVITY PER POSISI
            // =============================
            $activities = EmployeeActivity::where('id_position', $positionId)
                ->orderBy('activity_name')
                ->get();

            if ($activities->isEmpty()) {
                continue;
            }

            // =============================
            // AMBIL DATA TRANSAKSI
            // =============================
            $records = IndividualActivity::with(['employee', 'activity', 'activity.position'])
                ->whereHas('activity', function ($q) use ($positionId) {
                    $q->where('id_position', $positionId);
                })
                ->where('created_at', 'like', $periode . '%')
                ->get();

            if ($records->isEmpty()) {
                continue;
            }

            // =============================
            // REKAP DATA
            // =============================
            $rekap = [];
            foreach ($records as $r) {
                $emp = $r->employee->name ?? '-';
                $act = $r->activity->activity_name ?? '-';

                $rekap[$emp][$act] = ($rekap[$emp][$act] ?? 0) + 1;
            }

            // =============================
            // JUDUL POSISI
            // =============================
            $positionName = $records->first()?->activity?->position?->name ?? 'Tidak Diketahui';

            $sheet->setCellValue(
                'A' . $row,
                'POSISI / JENJANG: ' . strtoupper($positionName)
            );

            $sheet->mergeCells('A' . $row . ':Z' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;


            // =============================
            // HEADER TABEL
            // =============================
            $col = 'A';
            $sheet->setCellValue($col . $row, 'Nama Guru');
            $col++;

            foreach ($activities as $act) {
                $sheet->setCellValue($col . $row, $act->activity_name);
                $col++;
            }

            $sheet->setCellValue($col . $row, 'TOTAL');
            $lastCol = $col;

            // STYLE HEADER
            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)
                ->getFont()->setBold(true);

            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $row++;

            // =============================
            // ISI DATA
            // =============================
            $totalPerActivity = array_fill_keys(
                $activities->pluck('activity_name')->toArray(),
                0
            );

            foreach ($rekap as $guru => $dataAct) {
                $col = 'A';
                $sheet->setCellValue($col . $row, $guru);
                $col++;

                $rowTotal = 0;

                foreach ($activities as $act) {
                    $count = $dataAct[$act->activity_name] ?? 0;
                    $sheet->setCellValue($col . $row, $count);
                    $rowTotal += $count;
                    $totalPerActivity[$act->activity_name] += $count;
                    $col++;
                }

                $sheet->setCellValue($col . $row, $rowTotal);

                $sheet->getStyle('A' . $row . ':' . $lastCol . $row)
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $row++;
            }

            // =============================
            // BARIS TOTAL
            // =============================
            $col = 'A';
            $sheet->setCellValue($col . $row, 'TOTAL');
            $col++;

            $grandTotal = 0;
            foreach ($activities as $act) {
                $sheet->setCellValue($col . $row, $totalPerActivity[$act->activity_name]);
                $grandTotal += $totalPerActivity[$act->activity_name];
                $col++;
            }

            $sheet->setCellValue($col . $row, $grandTotal);

            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)
                ->getFont()->setBold(true);

            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THICK);

            $row += 3; // jarak antar tabel
        }

        // =============================
        // SIMPAN FILE
        // =============================
        $fileName = 'rekap_kegiatan_' . $periode . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $path = storage_path('app/' . $fileName);

        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path)->deleteFileAfterSend(true);
    }
}
