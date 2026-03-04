<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\PermitType;
use Illuminate\Http\Request;

class PermitTypeController extends Controller
{
    private $title = 'label.manage_permit_type';
    private $icon = 'ti ti-briefcase';
    private $path = 'backend.hr.permit-type.';

    public function index()
    {
        $count = PermitType::count();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count,
        ]);
    }

    public function create()
    {
        $level = [
            '1' => 'Level 1',
            '2' => 'Level 2',
            '3' => 'Level 3',
            '4' => 'Level 4',
        ];

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'level' => $level
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'permit_type' => 'required|string|max:100',
            'level' => 'required|in:1,2,3,4',
            'description' => 'nullable|string|max:255',
            'wage_status' => 'required|in:y,n',
        ], [
            'permit_type.required' => 'Nama jenis izin harus diisi',
            'level.required' => 'Level harus dipilih',
            'wage_status.required' => 'Status upah harus diisi',
        ]);

        PermitType::create([
            'permit_type' => $request->permit_type,
            'level' => $request->level,
            'description' => $request->description,
            'wage_status' => $request->wage_status,
        ]);

        return redirect()->route('hr.permit-type.index')->with('success', 'Berhasil menambahkan jenis izin');
    }

    public function destroy($id)
    {
        $permit_type = PermitType::findOrFail($id);
        $permit_type->delete();

        return redirect()->route('hr.permit-type.index')->with('success', 'Berhasil menghapus jenis izin');
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $position = PermitType::query();
        $position_count = $position->count();

        if (empty($search))
            $position_filter = $position;
        else
            $position_filter = $position->where('permit_type', 'like', '%' . $search . '%')
                ->orWhere('level', 'like', '%' . $search . '%');

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
}
