<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Departments;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    private $title = 'label.department_head';
    private $icon = 'ti ti-briefcase';
    private $path = 'backend.hr.department.';

    public function index()
    {
        $count = Departments::count();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count,
        ]);
    }

    public function create()
    {
        $employees = Employee::all();
        $position = Position::select('id', 'name')->get();

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'employees' => $employees,
            'position' => $position
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'position_id' => 'required',
            'employee_id' => 'required',
        ]);

        Departments::create([
            'position_id' => $request->position_id,
            'employee_id' => $request->employee_id,
        ]);

        return redirect()->route('hr.department.index')
            ->with('success', 'Kepala department berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        $position = Departments::findOrFail($id);
        $position->delete();

        return redirect()->route('hr.department.index')
            ->with('success', 'Kepala department berhasil dihapus.');
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $position = Departments::with([
            'position' => fn($q) => $q->select('id', 'name'),
            'employee' => fn($q) => $q->select('id', 'name')
        ]);
        $position_count = $position->count();

        if (empty($search))
            $position_filter = $position;
        else
            $position_filter = $position->whereHas('position', fn($q) => $q->where('name', 'like', '%' . $search . '%'))
                ->orWhereHas('employee', fn($q) => $q->where('name', 'like', '%' . $search . '%'));

        $position_count_filter = $position_filter->count();
        $position_data = $position_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $position_arr = [];

        foreach ($position_data as $a) {
            $push = $a->toArray();
            $push['encrypted_id'] = $a->encrypted_id;

            array_push($position_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $position_count,
            'recordsFiltered' => $position_count_filter,
            'data' => $position_arr
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
