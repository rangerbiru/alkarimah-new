<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\LocationMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LocationMasterController extends Controller
{
    private $title = 'label.location_master';
    private $icon = 'bx bx-current-location';
    private $path = 'backend.hr.master.location.';

    public function index()
    {
        $count = LocationMaster::count();
        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count
        ]);
    }

    public function create()
    {
        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:location_masters,name',
            'code' => 'required|string|max:100|unique:location_masters,code'
        ], [
            'name.required' => 'Nama lokasi wajib diisi.',
            'name.unique' => 'Nama lokasi sudah digunakan.',
            'code.required' => 'Kode lokasi wajib diisi.',
            'code.unique' => 'Kode lokasi sudah digunakan.',
        ]);

        LocationMaster::create([
            'name' => $request->name,
            'code' => Str::upper($request->code)
        ]);

        return redirect()->route('hr.location.index')
            ->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $location = LocationMaster::findOrFail($id);
        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'location' => $location
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:location_masters,name,' . $id,
            'code' => 'required|string|max:100|unique:location_masters,code,' . $id
        ], [
            'name.required' => 'Nama lokasi wajib diisi.',
            'name.unique' => 'Nama lokasi sudah digunakan.',
            'code.required' => 'Kode lokasi wajib diisi.',
            'code.unique' => 'Kode lokasi sudah digunakan.',
        ]);

        $item = LocationMaster::findOrFail($id);
        $item->update([
            'name' => $request->name,
            'code' => Str::upper($request->code)
        ]);

        return redirect()->route('hr.location.index')
            ->with('success', 'Lokasi berhasil diubah.');
    }

    public function destroy($id)
    {
        $item = LocationMaster::findOrFail($id);
        $item->delete();

        return redirect()->route('hr.location.index')
            ->with('success', 'Lokasi berhasil dihapus.');
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $item = LocationMaster::query();
        $item_count = $item->count();

        if (empty($search))
            $item_filter = $item;
        else
            $item_filter = $item->where('name', 'like', '%' . $search . '%')
                ->orWhere('code', 'like', '%' . $search . '%');

        $item_count_filter = $item_filter->count();
        $item_data = $item_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $item_arr = [];

        foreach ($item_data as $a) {
            $push = $a->toArray();
            $push['encrypted_id'] = $a->encrypted_id;

            array_push($item_arr, $push);
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $item_count,
            'recordsFiltered' => $item_count_filter,
            'data' => $item_arr
        ]);
    }
}