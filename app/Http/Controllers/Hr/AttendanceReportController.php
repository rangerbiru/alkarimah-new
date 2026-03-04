<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\AttendanceReport;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{
    private $title = 'label.attendance_employee';
    private $icon = 'bx bx-building';
    private $path = 'backend.hr.attendance.report.';

    public function index()
    {
        $count = AttendanceReport::count();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count
        ]);
    }

    public function destroy($id)
    {
        $report = AttendanceReport::find($id);
        $report->delete();

        return back()->with('success', 'Data absensi berhasil dihapus');
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? null;
        $limit  = $request->input('length') ?? 10;
        $start  = $request->input('start') ?? 0;

        $query = AttendanceReport::with('group', 'employee');

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
}
