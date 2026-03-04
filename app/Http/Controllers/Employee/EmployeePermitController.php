<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AllowedSubmissionEmployee;
use App\Models\AttendanceGroup;
use App\Models\AttendanceGroupMembers;
use App\Models\Departments;
use App\Models\Employee;
use App\Models\EmployeePermits;
use App\Models\PermitType;
use App\Models\Submissions;
use App\Notifications\EmployeeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmployeePermitController extends Controller
{
    private $title = 'label.employee_permit';
    private $path = 'backend.employee.permit.';
    private $icon = 'bx bx-user-check';

    public function index(Request $request)
    {
        $currentEmployee = Auth::user()?->employee;
        if (!$currentEmployee) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses.');
        }

        $currentEmployeeId = $currentEmployee->id;

        // SEMUA departemen yang dipimpin oleh pegawai ini
        $managedDepartments = Departments::where('employee_id', $currentEmployeeId)->get();
        $isHeadOfDepartment = $managedDepartments->isNotEmpty();

        $search = $request->input('search');

        $bulanIndo = [
            'januari' => 1,
            'februari' => 2,
            'maret' => 3,
            'april' => 4,
            'mei' => 5,
            'juni' => 6,
            'juli' => 7,
            'agustus' => 8,
            'september' => 9,
            'oktober' => 10,
            'november' => 11,
            'desember' => 12,
        ];

        $baseQuery = EmployeePermits::with([
            'employee' => fn($q) => $q->select('id', 'name', 'task_main'),
            'permitType',
            'department',
            'decisionBy',
        ])
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('created_at', 'desc');

        $applySearchFilter = function ($query) use ($search, $bulanIndo) {
            if (!$search) return;
            $searchLower = strtolower(trim($search));
            $bulanAngka = $bulanIndo[$searchLower] ?? null;

            $query->where(function ($q) use ($search, $bulanAngka) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('employee', fn($subQ) => $subQ->where('name', 'like', "%{$search}%"));

                if ($bulanAngka) {
                    $q->orWhereMonth('created_at', $bulanAngka);
                }
            });
        };

        if ($isHeadOfDepartment) {
            // Ambil semua ID departemen yang dia pimpin
            $managedDepartmentIds = $managedDepartments->pluck('id')->toArray();

            // 1. Izin milik sendiri
            $ownQuery = clone $baseQuery;
            $applySearchFilter($ownQuery);
            $ownQuery->where('employee_id', $currentEmployeeId);
            $ownPermits = $ownQuery->get();

            // 2. Izin bawahan (di semua departemen yang dia pimpin, kecuali dirinya sendiri)
            $subQuery = clone $baseQuery;
            $applySearchFilter($subQuery);
            $subQuery->whereIn('department_id', $managedDepartmentIds)
                ->where('employee_id', '!=', $currentEmployeeId);
            $subordinatePermits = $subQuery->get();

            // 3. Izin "Wakil Direktur" / Pengurus 
            $headEmployeeIds = Departments::pluck('employee_id')->unique()->toArray();

            $headQuery = clone $baseQuery;
            $applySearchFilter($headQuery);
            $headQuery->whereIn('employee_id', $headEmployeeIds);
            $headDepartmentPermits = $headQuery->get();

            $groupedOwn = $ownPermits->groupBy(fn($p) => $p->created_at->format('Y-m-d'));
            $groupedSub = $subordinatePermits->groupBy(fn($p) => $p->created_at->format('Y-m-d'));
            $groupedHead = $headDepartmentPermits->groupBy(fn($p) => $p->created_at->format('Y-m-d'));
        } else {
            // Pegawai biasa
            $applySearchFilter($baseQuery);
            $baseQuery->where('employee_id', $currentEmployeeId);
            $permits = $baseQuery->get();
            $groupedOwn = $permits->groupBy(fn($p) => $p->created_at->format('Y-m-d'));
            $groupedSub = collect();
            $groupedHead = collect();
        }

        $isPengurus = Departments::with('position')
            ->where('employee_id', $currentEmployeeId)
            ->whereHas('position', fn($q) => $q->where('name', 'Pengurus Pesantren'))
            ->exists();

        if (!$isPengurus || !$isHeadOfDepartment || !$managedDepartments) {
            DB::table('notifications')
                ->where('notifiable_id', $currentEmployeeId)
                ->update(['read_at' => now()]);
        }

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'groupedOwnPermits' => $groupedOwn,
            'groupedSubordinatePermits' => $groupedSub,
            'groupedHeadPermits' => $groupedHead,
            'isHeadOfDepartment' => $isHeadOfDepartment,
            'managedDepartments' => $managedDepartments,
            'isPengurus' => $isPengurus
        ]);
    }

    public function create()
    {
        $permitTypes = PermitType::select('id', 'permit_type', 'level')->get();

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'permitTypes' => $permitTypes,
        ]);
    }

    public function store(Request $request)
    {
        $employee = Auth::user()?->employee;

        $groupMember = AttendanceGroupMembers::where('employee_id', $employee?->id)->first();

        $position = AttendanceGroup::where('id', $groupMember->attendance_group_id)->first();

        $department = Departments::where('position_id', $position->position)->first();

        $request->validate([
            'permit_type_id' => 'required|exists:permit_types,id',
            'permit_start_time' => 'required_if:permit_type.level,1',
            'permit_hour_total' => 'required_if:permit_type.level,1',
            'permit_day_total' => 'required_if:permit_type.level,2',
            'date' => 'required|date_format:d-m-Y',
            'reason' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx|max:10240',
        ], [
            'permit_type_id.required' => 'Jenis izin harus dipilih.',
            'date.required' => 'Tanggal harus diisi.',
            'reason.required' => 'Alasan harus diisi.',
            'attachment.mimes' => 'Format file harus berupa jpg, jpeg, png, gif, pdf, doc, docx, xls, xlsx.',
            'attachment.max' => 'Ukuran file tidak boleh lebih dari 10MB.',
        ]);

        $date = \DateTime::createFromFormat('d-m-Y', $request->date);
        if (!$date) {
            return back()->withErrors(['date' => 'Format tanggal tidak valid.']);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $extension = $file->getClientOriginalExtension();
            $timestamp = time();
            $filename = "document_{$timestamp}.{$extension}";

            $attachmentPath = $file->storeAs('employee_permits', $filename, 'public');
        }

        // Simpan ke database
        $permit = EmployeePermits::create([
            'employee_id' => $employee->id,
            'permit_type_id' => $request->permit_type_id,
            'department_id' => $department->id,
            'name' => $employee->name,
            'date' => $date->format('Y-m-d'),
            'permit_start_time' => $request->permit_start_time,
            'permit_hour_total' => $request->permit_hour_total,
            'permit_day_total' => $request->permit_day_total,
            'reason' => $request->reason,
            'attachment' => $attachmentPath,
        ]);

        $this->sendPermitNotification($permit);

        return redirect()->route('employee.permit.index')
            ->with('success', 'Pengajuan izin berhasil ditambahkan.');
    }

    private function sendPermitNotification($permit)
    {
        $submitter = $permit->employee;
        if (!$submitter) return;

        // === 1. Cari KEPALA DEPARTEMEN dari departemen pemohon ===
        $headEmployeeId = Departments::where('id', $permit->department_id)->value('employee_id');
        $headUser = $headEmployeeId ? Employee::find($headEmployeeId) : null;

        // === 2. Cari SEMUA PENGURUS PESANTREN ===
        $pengurusEmployees = Departments::with('position')
            ->whereHas('position', fn($q) => $q->where('name', 'Pengurus Pesantren'))
            ->get()
            ->pluck('employee')
            ->filter();

        $recipients = collect();

        if ($headUser && $headUser->id !== $submitter->id) {
            $recipients->push($headUser);
        }

        $recipients = $recipients->merge($pengurusEmployees)->unique('id');

        if ($recipients->isEmpty()) {
            return;
        }

        // Kirim notifikasi
        foreach ($recipients as $user) {
            $user->notify(new EmployeeNotification(
                'Pengajuan Izin',
                $submitter->name . ' mengajukan izin: ' . $permit->permitType->permit_type,
                route('employee.permit.index'),
                'permit',
                'pending',
                $permit->id
            ));
        }
    }

    public function destroy($id)
    {
        $permit = EmployeePermits::findOrFail($id);

        if ($permit->attachment) {
            Storage::disk('public')->delete($permit->attachment);
        }

        DB::table('notifications')
            ->where('data->notifId', $permit->id)
            ->where('data->type', 'permit')
            ->where('data->status', 'pending')->delete();

        $permit->delete();

        return redirect()->route('employee.permit.index')
            ->with('success', 'Pengajuan izin berhasil dihapus.');
    }

    public function approve(Request $request, $id)
    {
        $permit = EmployeePermits::findOrFail($id);
        $permit->status = 'approved';
        $permit->note = $request->note;

        $permit->decision_by = Auth::user()->employee->id;
        $permit->save();

        if ($permit->employee) {
            $permit->employee->notify(new EmployeeNotification(
                'Izin Disetujui',
                $permit->note,
                route('employee.permit.index'),
                'permit',
                'approved',
                $permit->id
            ));
        }

        DB::table('notifications')
            ->where('data->notifId', (string) $permit->id)
            ->where('data->type', 'permit')
            ->where('data->status', 'pending')
            ->update([
                'data->status' => 'approved',
                'read_at' => now(),
            ]);

        return redirect()->route('employee.permit.index')
            ->with('success', 'Pengajuan izin berhasil disetujui.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $permit = EmployeePermits::findOrFail($id);
        $permit->status = 'rejected';
        $permit->note = $request->note;
        $permit->decision_by = Auth::user()->employee->id;
        $permit->save();

        if ($permit->employee) {
            $permit->employee->notify(new EmployeeNotification(
                'Izin Ditolak',
                $permit->note,
                route('employee.permit.index'),
                'permit',
                'rejected',
                $permit->id
            ));
        }

        DB::table('notifications')
            ->where('data->notifId', (string) $permit->id)
            ->where('data->type', 'permit')
            ->where('data->status', 'pending')
            ->update([
                'data->status' => 'rejected',
                'read_at' => now(),
            ]);

        return redirect()->route('employee.permit.index')
            ->with('success', 'Pengajuan izin berhasil ditolak.');
    }
}
