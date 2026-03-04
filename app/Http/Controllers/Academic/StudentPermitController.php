<?php

namespace App\Http\Controllers\Academic;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Student;
use App\Models\StudentPermit;
use App\Models\StudentPermitGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class StudentPermitController extends Controller
{
    private $title = 'label.student_permit';
    private $icon = 'bx bx bx-user';
    private $path = 'backend.academic.student-permit.';
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        if (Auth::user()->role == UserRole::OrangTua)
            return $this->indexParent();

        $count = StudentPermit::count();
        // $count = StudentPermit::with('group')->where('group.ustadz_id', Auth::user()->employee->id)->count();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count,
        ]);
    }

    public function indexParent()
    {
        $permits = StudentPermit::with([
            'student' => fn($query) => $query->select('id', 'name')
        ])->where('id_parent', Auth::user()->parent->id)->get();

        $ustadzName = null;

        if ($permits->isNotEmpty()) {
            foreach ($permits as $p) {
                $studentId = $p->student_id;
                $group = StudentPermitGroup::with('ustadz')->where('student_id', $studentId)->first();

                if ($group && $group->ustadz) {
                    $gender = $group->ustadz->gender;
                    $name = $group->ustadz->name;

                    $ustadzName = $gender === 'male'
                        ? 'Ustadz ' . $name
                        : ($gender === 'female' ? 'Ustadzah ' . $name : '-');
                }
            }
        }

        $count = StudentPermit::where('id_parent', Auth::user()->parent->id)->count();

        return view($this->path . 'index-parent', [
            'title' => 'Perizinan Orangtua',
            'icon' => $this->icon,
            'permits' => $permits,
            'count' => $count,
            'ustadzName' => $ustadzName,
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (Auth::user()->role == UserRole::OrangTua)
            return $this->createParent();

        $permitGroup = StudentPermitGroup::with('ustadz')->select('group_id', 'ustadz_id', 'group_name')->distinct()->get();

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'permitGroup' => $permitGroup
        ]);
    }

    public function createParent()
    {
        $permitGroup = StudentPermitGroup::with('ustadz')->select('group_id', 'ustadz_id', 'group_name')->distinct()->get();
        $student = Student::where('id_parent', Auth::user()->parent->id)->get();

        return view($this->path . 'create-parent', [
            'title' => 'Perizinan Orangtua',
            'icon' => $this->icon,
            'permitGroup' => $permitGroup,
            'student' => $student
        ]);
    }

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
    public function store(Request $request)
    {
        if (Auth::user()->role == UserRole::OrangTua)
            return $this->storeParent($request);

        $request->validate([
            'student_permit_group_id'    => 'required|exists:student_permit_groups,group_id',
            'student_id'                 => 'required',
            'permit_start_date'          => 'required|date',
            'permit_end_date'            => 'nullable|date|after_or_equal:permit_start_date',
            'purpose'                    => 'required|string',
            'other_purpose_description'  => 'nullable|string|max:255',
            'destination'                => 'nullable|string|max:255',
            'notes'                      => 'nullable|string',
        ]);

        $finalPurpose = $request->purpose === 'Lainnya'
            ? ($request->other_purpose_description ?: 'Lainnya')
            : $request->purpose;

        $idParent = Student::select('id_parent')->where('id', $request->student_id)->first(); //salah

        StudentPermit::create([
            'student_permit_group_id' => $request->student_permit_group_id,
            'id_parent'               => $idParent->id_parent,
            'student_id'              => $request->student_id,
            'permit_start_date'       => $request->permit_start_date,
            'permit_end_date'         => $request->permit_end_date,
            'purpose'                 => $finalPurpose,
            'destination'             => $request->destination,
            'notes'                   => $request->notes,
            'status'                  => 'pending', // default
        ]);

        return Redirect::route('academic.student-permit.index')->with('success', 'Data izin siswa berhasil ditambahkan.');
    }

    public function storeParent(Request $request)
    {
        $request->validate([
            'student_names'              => 'required|json',
            'permit_start_date'          => 'required|date',
            'permit_end_date'            => 'nullable|date|after_or_equal:permit_start_date',
            'purpose'                    => 'required|string',
            'other_purpose_description'  => 'nullable|string|max:255',
            'destination'                => 'nullable|string|max:255',
            'notes'                      => 'nullable|string',
        ]);

        $finalPurpose = $request->purpose === 'Lainnya'
            ? ($request->other_purpose_description ?: 'Lainnya')
            : $request->purpose;

        $students = json_decode($request->student_names, true);

        if (empty($students) || !is_array($students)) {
            return redirect()->back()->withErrors(['student_names' => 'Data siswa tidak valid.']);
        }

        foreach ($students as $student) {
            if (!isset($student['id'])) continue;

            $idParent = Student::where('id', $student['id'])->value('id_parent');

            $groupId = StudentPermitGroup::where('student_id', $student['id'])->value('group_id');

            StudentPermit::create([
                'student_permit_group_id' => $groupId,
                'id_parent'               => $idParent,
                'student_id'              => $student['id'],
                'permit_start_date'       => $request->permit_start_date,
                'permit_end_date'         => $request->permit_end_date,
                'purpose'                 => $finalPurpose,
                'destination'             => $request->destination,
                'notes'                   => $request->notes,
                'status'                  => 'pending',
            ]);
        }

        return redirect()->route('academic.student-permit.index')->with('success', 'Data izin siswa berhasil ditambahkan.');
    }




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
        $permit = StudentPermit::with(['student', 'group.ustadz'])->findOrFail($id);

        $permitGroup = StudentPermitGroup::select('group_id', 'group_name', 'ustadz_id')
            ->with(['ustadz' => fn($q) => $q->select('id', 'name')])
            ->groupBy('group_id', 'group_name', 'ustadz_id')
            ->get();

        $studentGroup = StudentPermitGroup::where('group_id', $permit->student_permit_group_id)
            ->select('student_id', 'student_name')
            ->get();

        $purposeOptions = [
            'Sakit' => 'Sakit',
            'Keluarga Meninggal Dunia' => 'Keluarga Meninggal Dunia',
            'Acara Keluarga' => 'Acara Keluarga',
            'Perjalanan Penting' => 'Perjalanan Penting',
            'Keperluan Darurat' => 'Keperluan Darurat',
            'Lainnya' => 'Lainnya',
        ];

        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'permit' => $permit,
            'permitGroup' => $permitGroup,
            'studentGroup' => $studentGroup,
            'purposeOptions' => $purposeOptions
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'student_permit_group_id'    => 'required|exists:student_permit_groups,group_id',
            'student_id'                 => 'required',
            'permit_start_date'          => 'required|date',
            'permit_end_date'            => 'nullable|date|after_or_equal:permit_start_date',
            'purpose'                    => 'required|string',
            'other_purpose_description'  => 'nullable|string|max:255',
            'destination'                => 'nullable|string|max:255',
            'notes'                      => 'nullable|string',
        ]);

        $finalPurpose = $request->purpose === 'Lainnya'
            ? ($request->other_purpose_description ?: 'Lainnya')
            : $request->purpose;

        $idParent = Student::select('id_parent')->where('id', $request->student_id)->first();

        $permit = StudentPermit::findOrFail($id);
        $permit->update([
            'student_permit_group_id' => $request->student_permit_group_id,
            'student_id'              => $request->student_id,
            'id_parent'               => $idParent->id_parent,
            'permit_start_date'       => $request->permit_start_date,
            'permit_end_date'         => $request->permit_end_date,
            'purpose'                 => $finalPurpose,
            'destination'             => $request->destination,
            'notes'                   => $request->notes,
        ]);

        return redirect()->route('academic.student-permit.index')
            ->with('success', 'Data izin siswa berhasil diperbarui.');
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $permit = StudentPermit::findOrFail($id);

        if ($permit->delete()) {
            return Redirect::route('academic.student-permit.index')->with('success', __('message.delete_success', ['label' => __($this->title)]));
        }
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $student = StudentPermit::select('*')
            ->with([
                'student' => fn($query) => $query->select('id', 'name', 'id_class'),
                'class' => fn($query) => $query->select('id', 'name'),
                'group' => fn($query) => $query->select('id', 'group_name'),
                'approver' => fn($query) => $query->select('id', 'name')
            ]);

        if (Auth::user()->role === UserRole::OrangTua)
            $student = $student->where('id_parent', Auth::user()->parent->id); //id_parent:3, id:3

        $student_count = $student->count();

        if (empty($search))
            $student_filter = $student;
        else {
            $student_filter = $student->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('nis', 'like', '%' . $search . '%')
                    ->orWhereHas('class', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('parent', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $student_count_filter = $student_filter->count();
        $student_data = $student_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $student_arr = [];

        foreach ($student_data as $s) {
            $push = $s->toArray();
            $push['encrypted_id'] = $s->encrypted_id;
            $push['gender_name'] = $s->gender_name;
            $push['status_badge'] = $s->status_badge;

            array_push($student_arr, $push);
        }

        $response = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $student_count,
            'recordsFiltered' => $student_count_filter,
            'data' => $student_arr
        ];

        return response()->json($response);
    }
}
