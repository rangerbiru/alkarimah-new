<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceSchedule;
use App\Models\Position;
use Illuminate\Http\Request;

class AttendanceScheduleController extends Controller
{
    private $title = [
        'employee' => 'label.attendance_schedule',
        'rights' => 'label.access_rights'
    ];
    private $icon = 'bx bx-building';
    private $path = 'backend.hr.attendance.';
    public function index()
    {
        $count = EmployeeAttendanceSchedule::count();

        return view($this->path . 'index', [
            'title' => __($this->title['employee']),
            'icon' => $this->icon,
            'count' => $count
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? null;
        $limit = $request->input('length') ?? 10;
        $start = $request->input('start') ?? 0;

        $query = EmployeeAttendanceSchedule::with('employee');
        $recordsTotal = $query->count();

        if (!empty($search)) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('day_of_week', 'like', '%' . $search . '%');
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
}
