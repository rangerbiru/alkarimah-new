<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\LocationMaster;
use App\Models\UnitMaster;
use App\Models\ViolationTypes;
use Illuminate\Http\Request;

class ViolationTypeMasterController extends Controller
{
    private $title = 'label.violation_master';
    private $icon = 'bx bx-error';
    private $path = 'backend.hr.master.violation.';

    public function index()
    {
        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
        ]);
    }

    public function create()
    {
        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:violation_types,code',
            'group' => 'required|string|max:100',
            'impact_level' => 'required|in:rendah,menengah,tinggi,sangat tinggi,fatal',
            'description' => 'required|string|max:255',
            'points' => 'required|integer|min:0',
            'status' => 'required|in:aktif, non aktif',
        ], [
            'code.required' => "kode harus diisi",
            'code.unique' => "kode sudah ada",
            'group.required' => "kelompok harus diisi",
            'impact_level.required' => "kategori dampak harus diisi",
            'description.required' => "deskripsi harus diisi",
            'points.required' => "poin harus diisi",
            'status.required' => "status harus diisi",
        ]);

        ViolationTypes::create($request->only([
            'code',
            'group',
            'impact_level',
            'description',
            'points',
            'status'
        ]));

        return redirect()->route('hr.violation.index')
            ->with('success', __('Violation type created successfully.'));
    }

    public function edit($id)
    {
        $violation = ViolationTypes::findOrFail($id);

        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'violation' => $violation,
        ]);
    }

    public function update(Request $request, $id)
    {
        $violationType = ViolationTypes::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:20|unique:violation_types,code,' . $violationType->id,
            'group' => 'required|string|max:100',
            'impact_level' => 'required|in:rendah,menengah,tinggi,sangat tinggi,fatal',
            'description' => 'required|string|max:255',
            'points' => 'required|integer|min:0',
            'status' => 'required|in:aktif, non aktif',
        ], [
            'code.required' => "kode harus diisi",
            'code.unique' => "kode sudah ada",
            'group.required' => "kelompok harus diisi",
            'impact_level.required' => "kategori dampak harus diisi",
            'description.required' => "deskripsi harus diisi",
            'points.required' => "poin harus diisi",
            'status.required' => "status harus diisi",
        ]);

        $violationType->update($request->only([
            'code',
            'group',
            'impact_level',
            'description',
            'points',
            'status'
        ]));

        return redirect()->route('hr.violation.index')
            ->with('success', __('Violation type updated successfully.'));
    }

    public function destroy($id)
    {
        $violationType = ViolationTypes::findOrFail($id);
        $violationType->delete();

        return response()->json([
            'success' => true,
            'message' => __('Violation type deleted successfully.')
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search.value');
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);

        $query = ViolationTypes::query();

        // Filter pencarian (cari di code, group, description)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('group', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $totalRecords = ViolationTypes::count();
        $filteredRecords = $query->count();
        $data = $query->skip($start)
            ->take($limit)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
}
