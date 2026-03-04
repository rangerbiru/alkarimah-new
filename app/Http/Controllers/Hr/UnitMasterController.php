<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\LocationMaster;
use App\Models\UnitMaster;
use Illuminate\Http\Request;

class UnitMasterController extends Controller
{
    private $title = 'label.unit_master';
    private $icon = 'ti ti-home';
    private $path = 'backend.hr.master.unit.';

    public function index()
    {
        $count = UnitMaster::count();
        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count
        ]);
    }

    public function create()
    {
        $locations = LocationMaster::pluck('name', 'id');

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'locations' => $locations
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:location_masters,id',
            'unit' => 'required|string|max:255|unique:unit_masters,unit',
        ], [
            'unit.required' => 'Nama unit wajib diisi.',
            'unit.unique' => 'Nama unit sudah digunakan.',
        ]);

        UnitMaster::create([
            'location_id' => $request->location_id,
            'unit' => $request->unit,
        ]);

        return redirect()->route('hr.unit.index')
            ->with('success', 'Unit berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $unit = UnitMaster::findOrFail($id);
        $locations = LocationMaster::pluck('name', 'id');

        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'unit' => $unit,
            'locations' => $locations
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'location_id' => 'required|exists:location_masters,id',
            'unit' => 'required|string|max:255|unique:unit_masters,unit,' . $id,
        ], [
            'unit.required' => 'Nama unit wajib diisi.',
            'unit.unique' => 'Nama unit sudah digunakan.',
        ]);

        $unit = UnitMaster::findOrFail($id);
        $unit->update([
            'location_id' => $request->location_id,
            'unit' => $request->unit,
        ]);

        return redirect()->route('hr.unit.index')
            ->with('success', 'Unit berhasil diubah.');
    }

    public function destroy($id)
    {
        $unit = UnitMaster::findOrFail($id);
        $unit->delete();

        return redirect()->route('hr.location.index')
            ->with('success', 'Unit berhasil dihapus.');
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $unit = UnitMaster::with('location');
        $unit_count = $unit->count();

        if (empty($search))
            $unit_filter = $unit;
        else
            $unit_filter = $unit->where('name', 'like', '%' . $search . '%');

        $unit_count_filter = $unit_filter->count();
        $unit_data = $unit_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $unit_arr = [];

        foreach ($unit_data as $a) {
            $push = $a->toArray();
            $push['encrypted_id'] = $a->encrypted_id;

            array_push($unit_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $unit_count,
            'recordsFiltered' => $unit_count_filter,
            'data' => $unit_arr
        ]);
    }
}
