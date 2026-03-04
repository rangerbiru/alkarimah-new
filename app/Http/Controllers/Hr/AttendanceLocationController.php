<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\AttendanceGroup;
use App\Models\AttendanceLocation;
use Illuminate\Http\Request;

class AttendanceLocationController extends Controller
{
    private $title = 'label.attendance_location';
    private $icon = 'bx bx-current-location';
    private $path = 'backend.hr.attendance.location.';

    public function index()
    {
        $location = AttendanceLocation::with('group')->get();
        $count = $location->count();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'location' => $location,
            'count' => $count
        ]);
    }

    public function create()
    {
        $groupId = AttendanceGroup::pluck('group_name', 'id');

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'groupId' => $groupId
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'location_name' => 'required',
            'coordinate' => 'required',
            'attendance_location' => 'required',
            'attendance_radius' => 'required',
        ]);

        AttendanceLocation::create($request->all());
        return redirect()->route('hr.attendance.location.index')->with('success', 'Data lokasi berhasil disimpan');
    }

    public function edit($id)
    {
        $location = AttendanceLocation::findOrFail($id);
        $groupId = AttendanceGroup::pluck('group_name', 'id');

        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'location' => $location,
            'groupId' => $groupId
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'location_name' => 'required',
            'coordinate' => 'required',
            'attendance_location' => 'required',
            'attendance_radius' => 'required',
        ]);

        $location = AttendanceLocation::findOrFail($id);
        $location->update($request->all());
        return redirect()->route('hr.attendance.location.index')->with('success', 'Data lokasi berhasil diubah');
    }

    public function destroy($id)
    {
        $location = AttendanceLocation::findOrFail($id);
        $location->delete();
        return redirect()->route('hr.attendance.location.index')->with('success', 'Data lokasi berhasil dihapus');
    }


    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? null;
        $limit  = $request->input('length') ?? 10;
        $start  = $request->input('start') ?? 0;

        $query = AttendanceLocation::with('group');

        $recordsTotal = $query->count();

        if (!empty($search)) {
            $query->where('location_name', 'like', '%' . $search . '%');
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
