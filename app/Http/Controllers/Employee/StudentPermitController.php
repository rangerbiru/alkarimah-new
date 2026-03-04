<?php

namespace App\Http\Controllers\Employee;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Student;
use App\Models\StudentPermit;
use App\Models\StudentPermitGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class StudentPermitController extends Controller
{
    private $title = 'label.student_permit';
    private $icon = 'bx bx bx-user';
    private $path = 'backend.employee.student-permit.';
    /**
     * Display a listing of the resource.
     */

    public function ustadzId()
    {
        $ustadzId = Employee::where('id_user', Auth::id())->first();
        return $ustadzId->id;
    }

    public function index()
    {

        $permits = DB::table('student_permits')
            ->join('student_permit_groups', 'student_permits.student_permit_group_id', '=', 'student_permit_groups.group_id')
            // ->where('student_permit_groups.ustadz_id', $ustadzId)
            ->whereColumn('student_permit_groups.student_id', 'student_permits.student_id')
            ->select(
                'student_permits.*',
                'student_permit_groups.group_name',
                'student_permit_groups.student_name',
                'student_permit_groups.ustadz_id'
            )->get();

        foreach ($permits as $p) {
            $p->ustadz_name = Employee::where('id', $p->ustadz_id)->value('name');
        }

        $count = $permits->count();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'permits' => $permits,
            'count' => $count,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    // public function create()
    // {
    //     $ustadzId = $this->ustadzId();
    //     $permitGroup = StudentPermitGroup::with('ustadz')->where('ustadz_id', $ustadzId)->first();

    //     return view($this->path . 'create', [
    //         'title' => __($this->title),
    //         'icon' => $this->icon,
    //         'permitGroup' => $permitGroup
    //     ]);
    // }

    public function getStudentsByGroup(Request $request)
    {
        $students = StudentPermitGroup::where('group_id', $request->group_id)
            ->select('student_id', 'student_name')
            ->get();

        return response()->json($students);
    }

    public function getGroupByStudent(Request $request)
    {
        $groups = StudentPermitGroup::with('ustadz')->where('student_id', $request->student_id)
            ->select('group_id', 'group_name', 'ustadz_id')
            ->get();

        return response()->json($groups);
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'student_permit_group_id'    => 'required',
    //         'student_id'                 => 'required',
    //         'permit_start_date'          => 'required|date',
    //         'permit_end_date'            => 'nullable|date|after_or_equal:permit_start_date',
    //         'purpose'                    => 'required|string',
    //         'other_purpose_description'  => 'nullable|string|max:255',
    //         'destination'                => 'nullable|string|max:255',
    //         'notes'                      => 'nullable|string',
    //     ]);

    //     $finalPurpose = $request->purpose === 'Lainnya'
    //         ? ($request->other_purpose_description ?: 'Lainnya')
    //         : $request->purpose;

    //     $idParent = DB::table('student')
    //         ->where('id', $request->student_id)
    //         ->value('id_parent');

    //     $data = [
    //         'student_permit_group_id' => $request->student_permit_group_id,
    //         'student_id'              => $request->student_id,
    //         'id_parent'               => $idParent,
    //         'permit_start_date'       => $request->permit_start_date,
    //         'permit_end_date'         => $request->permit_end_date,
    //         'purpose'                 => $finalPurpose,
    //         'destination'             => $request->destination,
    //         'notes'                   => $request->notes,
    //         'status'                  => 'approved',
    //     ];

    //     $ustadzId = $this->ustadzId();

    //     if ($data['status'] === 'approved') {
    //         $data['approved_by'] = $ustadzId;
    //         $data['approved_at'] = now();
    //     }

    //     StudentPermit::create($data);

    //     return Redirect::route('employee.student-permit.index')
    //         ->with('success', 'Data izin siswa berhasil ditambahkan');
    // }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search.value');
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);

        $ustadzId = $this->ustadzId();

        // $group = StudentPermitGroup::where('ustadz_id', $ustadzId)->first();

        // if (!$group) {
        //     return response()->json([
        //         'draw' => $request->input('draw'),
        //         'recordsTotal' => 0,
        //         'recordsFiltered' => 0,
        //         'data' => [],
        //     ]);
        // }

        $query = DB::table('student_permits')
            ->join('student_permit_groups', 'student_permits.student_permit_group_id', '=', 'student_permit_groups.group_id')
            // ->where('student_permit_groups.ustadz_id', $ustadzId)
            ->whereColumn('student_permit_groups.student_id', 'student_permits.student_id')
            ->select(
                'student_permits.*',
                'student_permit_groups.group_name',
                'student_permit_groups.student_name',
                'student_permit_groups.ustadz_id'
            );

        // Hitung total sebelum filter
        $recordsTotal = $query->count();

        // Terapkan filter jika ada pencarian
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('student_permit_groups.student_name', 'like', "%$search%")
                    ->orWhere('student_permits.keperluan', 'like', "%$search%")
                    ->orWhere('student_permits.status', 'like', "%$search%");
            });
        }

        $recordsFiltered = $query->count();

        $data = $query->orderBy('student_permits.created_at', 'desc')
            ->offset($start)
            ->limit($limit)
            ->get();

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
            'ustadzIdLogin' => $ustadzId
        ]);
    }

    public function approve($id)
    {
        $ustadzId = $this->ustadzId();
        $permit = StudentPermit::find($id);
        $permit->update([
            'status' => 'approved',
            'approved_by' => $ustadzId,
            'approved_at' => now(),
            'permission_note' => request('permission_note')
        ]);

        return response()->json(['message' => 'Data izin siswa berhasil disetujui.']);
    }


    public function reject($id)
    {
        $ustadzId = $this->ustadzId();
        $permit = StudentPermit::find($id);
        $permit->update([
            'status' => 'rejected',
            'approved_by' => $ustadzId,
            'approved_at' => now(),
            'permission_note' => request('permission_note')
        ]);

        return response()->json(['message' => 'Data izin siswa berhasil ditolak.']);
    }
}
