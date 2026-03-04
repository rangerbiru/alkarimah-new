<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LunchRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LunchReportController extends Controller
{
    private $title = 'label.lunch_attendance_report';
    private $path = 'backend.employee.lunch-report.';
    private $icon = 'bx bxs-bowl-hot';

    public function index()
    {
        $totalLunch = LunchRequest::whereDate('created_at', \Carbon\Carbon::today()->format('Y-m-d'))->count();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'totalLunch' => $totalLunch
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search.value');
        $limit  = $request->input('length', 10);
        $start  = $request->input('start', 0);
        $filterDate = $request->input('filter_date');

        $date = $filterDate ? \Carbon\Carbon::parse($filterDate)->format('Y-m-d') : \Carbon\Carbon::today()->format('Y-m-d');

        $totalLunchSummary = LunchRequest::whereDate('created_at', $date)->count();

        $query = LunchRequest::select('attendance_group.group_name')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("GROUP_CONCAT(employee.name ORDER BY employee.name SEPARATOR '||') as employee_names")
            ->join('employee', 'lunch_requests.employee_id', '=', 'employee.id')
            ->join('attendance_group', 'lunch_requests.attendance_group_id', '=', 'attendance_group.id')
            ->whereDate('lunch_requests.created_at', $date)
            ->groupBy('attendance_group.group_name');

        // Filter pencarian
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('attendance_group.group_name', 'like', "%{$search}%")
                    ->orWhere('employee.name', 'like', "%{$search}%");
            });
        }

        $data = $query->limit($limit)
            ->offset($start)
            ->orderBy('attendance_group.group_name')
            ->get()
            ->map(function ($row) {
                return [
                    'task_main' => $row->group_name ?? 'Tidak diketahui',
                    'total' => (int) $row->total,
                    'employee_names' => $row->employee_names ?? '-',
                ];
            });

        $recordsTotal = $data->count();
        $recordsFiltered = $data->count();

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
            'total_lunch_summary' => $totalLunchSummary
        ]);
    }
}
