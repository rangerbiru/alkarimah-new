<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\AttendanceGroup;
use App\Models\AttendanceGroupMembers;
use App\Models\Employee;
use Illuminate\Http\Request;

class AttendanceMemberController extends Controller
{
    private $title = 'label.attendance_member';
    private $icon = 'bx bx-building';
    private $path = 'backend.hr.attendance.member.';

    public function index()
    {
        $count = AttendanceGroupMembers::count();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count
        ]);
    }

    public function create()
    {
        $employees = Employee::all();
        $groupAttendance = AttendanceGroup::all();

        return view($this->path . 'create', [
            'title' => "Tambah " . __($this->title),
            'icon' => $this->icon,
            'employees' => $employees,
            'groupAttendance' => $groupAttendance
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'attendance_group_id' => 'required|exists:attendance_group,id',
            'employee_id' => 'required|string',
        ]);

        $employeeIds = array_filter(explode(',', $request->employee_id));

        foreach ($employeeIds as $employeeId) {
            AttendanceGroupMembers::create([
                'attendance_group_id' => $request->attendance_group_id,
                'employee_id'         => $employeeId,
                'joined_at'           => now(),
            ]);
        }

        return redirect()->route('hr.attendance.member.index')
            ->with('success', 'Data berhasil disimpan.');
    }

    public function destroy($id)
    {
        AttendanceGroupMembers::destroy($id);
        return back()->with('success', 'Data berhasil dihapus');
    }


    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? null;
        $limit  = $request->input('length') ?? 10;
        $start  = $request->input('start') ?? 0;

        $query = AttendanceGroupMembers::with('attendanceGroup', 'employee');

        $recordsTotal = $query->count();

        if (!empty($search)) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })->orWhereHas('attendanceGroup', function ($q) use ($search) {
                $q->where('group_name', 'like', '%' . $search . '%');
            });
        }

        $recordsFiltered = $query->count();

        $data = $query->limit($limit)
            ->offset($start)
            ->orderBy('joined_at', 'desc')
            ->get();



        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data
        ]);
    }

    public function dataEmployee(Request $request)
    {
        $search = $request->input('search')['value'] ?? null;
        $limit  = $request->input('length') ?? 10;
        $start  = $request->input('start') ?? 0;

        $query = Employee::query();

        $recordsTotal = $query->count();

        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('gender', 'like', '%' . $search . '%')
                ->orWhere('task_main', 'like', '%' . $search . '%');;
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
}
