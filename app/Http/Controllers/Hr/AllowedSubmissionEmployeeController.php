<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\AllowedSubmissionEmployee;
use App\Models\Employee;
use App\Models\EmployeeActivity;
use Illuminate\Http\Request;

class AllowedSubmissionEmployeeController extends Controller
{
    private $title = 'label.submission_employee';
    private $icon = 'bx bx-building';
    private $path = 'backend.hr.allowed-submission.';

    public function index()
    {
        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
        ]);
    }

    public function create()
    {
        $employees = Employee::all();

        return view($this->path . 'create', [
            'title' => "Tambah " . __($this->title),
            'icon' => $this->icon,
            'employees' => $employees,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string',
            'position' => 'required|string',
        ]);

        AllowedSubmissionEmployee::create([
            'employee_id'         => $request->employee_id,
            'position'            => $request->position,
        ]);

        return redirect()->route('hr.allowed-submission.index')
            ->with('success', 'Data berhasil disimpan.');
    }

    public function destroy($id)
    {
        AllowedSubmissionEmployee::where('id', $id)->delete();
        return redirect()->route('hr.allowed-submission.index')
            ->with('success', 'Data berhasil dihapus.');
    }


    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? null;
        $limit  = $request->input('length') ?? 10;
        $start  = $request->input('start') ?? 0;

        $query = AllowedSubmissionEmployee::with('employee');

        $recordsTotal = $query->count();

        if (!empty($search)) {
            $query->where('activity_name', 'like', '%' . $search . '%')
                ->orWhere('activity_type', 'like', '%' . $search . '%');
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
