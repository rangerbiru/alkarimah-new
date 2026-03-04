<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\EmployeeActivity;
use App\Models\IndividualActivity;
use App\Models\Position;
use Illuminate\Http\Request;

class EmployeeActivityController extends Controller
{
    private $title = 'label.employee_activity';
    private $path = 'backend.hr.employee-activity.';
    private $icon = 'bx bxs-user-rectangle';

    public function index()
    {
        $activity = EmployeeActivity::with([
            'position' => fn($query) => $query->select('id', 'name', 'branch_id'),
        ])->get();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'activity' => $activity
        ]);
    }

    public function create()
    {
        $type = [
            'pribadi' => 'Pribadi',
            'kepanitiaan' => 'Kepanitiaan',
        ];

        $position = Position::select('id', 'name')->get();

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'type' => $type,
            'position' => $position
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'activity_name' => 'required|string',
            'activity_type' => 'required|string',
            'id_position' => 'required|integer'
        ], [
            'activity_name.required' => 'Nama kegiatan harus diisi.',
            'activity_type.required' => 'Tipe kegiatan harus diisi.',
            'id_position.required' => 'Posisi harus diisi.',
        ]);

        $data = [
            'activity_name' => $request->activity_name,
            'activity_type' => $request->activity_type,
            'id_position' => $request->id_position,
        ];

        EmployeeActivity::create($data);

        return redirect()->route('hr.employee-activity.index')
            ->with('success', 'Kegiatan pegawai berhasil disimpan.');
    }

    public function edit($id)
    {
        $activity = EmployeeActivity::findOrFail($id);
        $type = [
            'pribadi' => 'Pribadi',
            'kepanitiaan' => 'Kepanitiaan',
        ];

        $position = Position::select('id', 'name')->get();

        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'activity' => $activity,
            'type' => $type,
            'position' => $position
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'activity_name' => 'required|string',
            'activity_type' => 'required|string',
            'id_position' => 'required|integer'
        ], [
            'activity_name.required' => 'Nama kegiatan harus diisi.',
            'activity_type.required' => 'Tipe kegiatan harus diisi.',
            'id_position.required' => 'Posisi harus diisi.',
        ]);

        $data = [
            'activity_name' => $request->activity_name,
            'activity_type' => $request->activity_type,
            'id_position' => $request->id_position,
        ];

        EmployeeActivity::where('id', $id)->update($data);

        return redirect()->route('hr.employee-activity.index')
            ->with('success', 'Kegiatan pegawai berhasil diubah.');
    }

    public function destroy($id)
    {
        $activity = IndividualActivity::where('id_activity', $id)->first();
        // if ($activity) {
        //     return back()->with('error', 'Kegiatan pegawai sedang digunakan, tidak dapat dihapus.');
        // }
        EmployeeActivity::destroy($id);
        return back()->with('success', 'Kegiatan pegawai berhasil dihapus');
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? null;
        $limit  = $request->input('length') ?? 10;
        $start  = $request->input('start') ?? 0;

        $query = EmployeeActivity::with('position');

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
