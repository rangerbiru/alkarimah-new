<?php

namespace App\Http\Controllers\Academic;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Student;
use App\Models\StudentPermitGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class StudentPermitGroupController extends Controller
{
    private $title = 'label.student_permit_group';
    private $icon = 'bx bx bx-user';
    private $path = 'backend.academic.student-permit.';
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $count = StudentPermitGroup::distinct('group_id')->count();

        $permitGroups = StudentPermitGroup::all()->groupBy('group_id');

        return view($this->path . 'index-group', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count,
            'permitGroups' => $permitGroups
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $ustadz_list = Employee::with('user')
            ->whereHas('user', function ($query) {
                $query->whereIn('role', ['pegawai', 'admin']);
            })
            ->select('id', 'name')
            ->orderBy('id')
            ->get();


        $student_list = Student::select('id', 'name')
            ->orderBy('id')
            ->get();


        return view($this->path . 'create-group', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'ustadz_list' => $ustadz_list,
            'student_list' => $student_list
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'ustadz_id'     => 'required|integer',
            'group_name'    => 'required|string|max:255',
            'student_name'  => 'required|string',
            'description'   => 'nullable|string',
        ]);

        try {
            $students = json_decode($request->student_name);

            if (!is_array($students)) {
                return back()->with('error', 'Format siswa tidak valid.');
            }

            $lastGroupId = StudentPermitGroup::max('group_id') ?? 0;
            $newGroupId = $lastGroupId + 1;

            foreach ($students as $student) {
                StudentPermitGroup::create([
                    'group_id'     => $newGroupId,
                    'ustadz_id'    => $request->ustadz_id,
                    'group_name'   => $request->group_name,
                    'student_id'   => $student->id,
                    'student_name' => $student->name,
                    'description'  => $request->description,
                ]);
            }

            return redirect()->route('academic.student-permit-group.index')
                ->with('success', __('message.create_success', ['label' => __($this->title)]));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
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
        $groupId = StudentPermitGroup::where('group_id', $id)->value('group_id');
        $permitGroup = StudentPermitGroup::where('group_id', $id)->first();

        $studentGroup = StudentPermitGroup::select('student_id', 'student_name')->where('group_id', $groupId)->get();

        $ustadz_list = Employee::with('user')
            ->whereHas('user', function ($query) {
                $query->whereIn('role', ['pegawai', 'admin']);
            })
            ->select('id', 'name')
            ->orderBy('id')
            ->get();

        $student_list = Student::select('id', 'name')
            ->orderBy('id')
            ->get();

        return view($this->path . 'edit-group', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'permitGroup' => $permitGroup,
            'ustadz_list' => $ustadz_list,
            'student_list' => $student_list,
            'groupId' => $groupId,
            'studentGroup' => $studentGroup
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'ustadz_id'   => 'required|integer',
            'group_name'  => 'required|string|max:255',
            'student_name' => 'required|string', // JSON string
            'description' => 'nullable|string',
        ]);

        try {
            $students = json_decode($request->student_name);

            if (!is_array($students)) {
                return back()->with('error', 'Format siswa tidak valid.');
            }

            // Ambil semua data lama dari database
            $existingStudents = StudentPermitGroup::where('group_id', $id)->get();

            // Simpan ID student yang sudah ditangani (untuk pengecekan delete)
            $handledStudentIds = [];

            foreach ($students as $student) {
                $existing = $existingStudents->firstWhere('student_id', $student->student_id);

                if ($existing) {
                    // Update data siswa lama
                    $existing->update([
                        'ustadz_id'    => $request->ustadz_id,
                        'group_name'   => $request->group_name,
                        'student_name' => $student->student_name,
                        'description'  => $request->description,
                    ]);
                } else {
                    // Tambahkan data siswa baru
                    StudentPermitGroup::create([
                        'group_id'     => $id,
                        'ustadz_id'    => $request->ustadz_id,
                        'group_name'   => $request->group_name,
                        'student_id'   => $student->student_id,
                        'student_name' => $student->student_name,
                        'description'  => $request->description,
                    ]);
                }

                $handledStudentIds[] = $student->student_id;
            }

            // Hapus siswa yang tidak lagi ada dalam input
            StudentPermitGroup::where('group_id', $id)
                ->whereNotIn('student_id', $handledStudentIds)
                ->delete();

            return redirect()->route('academic.student-permit-group.index')
                ->with('success', __('message.update_success', ['label' => __($this->title)]));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }




    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $deleted = StudentPermitGroup::where('group_id', $id)->delete();

        if ($deleted) {
            return Redirect::route('academic.student-permit-group.index')
                ->with('success', __('message.delete_success', ['label' => __($this->title)]));
        }

        return back()->with('error', 'Gagal menghapus data.');
    }


    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? '';
        $limit = $request->input('length');
        $start = $request->input('start');

        $groups = StudentPermitGroup::with('ustadz')
            ->selectRaw('group_id, group_name, ustadz_id, description, MAX(created_at) as created_at')
            ->groupBy('group_id', 'group_name', 'ustadz_id', 'description');

        // Hitung total data
        $totalCount = $groups->count();

        // Filter pencarian
        if (!empty($search)) {
            $groups = $groups->where(function ($query) use ($search) {
                $query->where('group_name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhereHas('ustadz', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $filteredCount = $groups->count();

        // Ambil data dengan pagination
        $data = $groups->skip($start)
            ->take($limit)
            ->latest()
            ->get();

        $result = [];

        foreach ($data as $group) {
            $studentCount = StudentPermitGroup::where('group_id', $group->group_id)->count();

            $result[] = [
                'group_id' => $group->group_id,
                'group_name' => $group->group_name,
                'ustadz_name' => $group->ustadz->name ?? '-',
                'description' => $group->description,
                'student_count' => $studentCount,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalCount,
            'recordsFiltered' => $filteredCount,
            'data' => $result,
        ]);
    }
}
