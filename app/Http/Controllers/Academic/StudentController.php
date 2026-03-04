<?php

namespace App\Http\Controllers\Academic;

use App\Enums\UserRole;
use App\Helpers\Common;
use App\Helpers\Upload;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentChangeRequest;
use App\Http\Requests\StudentExculRequest;
use App\Http\Requests\StudentParentRequest;
use App\Http\Requests\StudentRequest;
use App\Http\Requests\StudentSetRequest;
use App\Models\Asrama;
use App\Models\Attachment;
use App\Models\Excul;
use App\Models\Halaqah;
use App\Models\Student;
use App\Models\Parents;
use App\Models\StudentDisplacementHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class StudentController extends Controller
{
    private $title = 'label.student';
    private $icon = 'bx bx bx-user';
    private $path = 'backend.academic.student.';

    public function index()
    {
        if (Auth::user()->role == UserRole::OrangTua)
            return $this->indexParent();

        $count = Student::count();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count,
        ]);
    }

    private function indexParent()
    {
        $students = Student::select('id', 'id_class', 'nis', 'name', 'gender', 'file_photo')
            ->with([
                'class' => fn($query) => $query->select('id', 'name'),
                'photo' => fn($query) => $query->select('id'),
            ])
            ->whereIdParent(Auth::user()->parent->id)
            ->orderBy('name')
            ->get();

        return view($this->path . 'index-parent', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'students' => $students
        ]);
    }

    public function show(Student $student)
    {
        return view($this->path . 'show', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'student' => $student
        ]);
    }

    public function historyDisplacement(Student $student)
    {
        $count = StudentDisplacementHistory::whereIdStudent($student->id)->count();

        return view($this->path . 'history-displacement', [
            'title' => __('label.move_history'),
            'icon' => 'ti ti-history-toggle',
            'student' => $student,
            'count' => $count,
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $student = Student::select('id', 'id_parent', 'id_class', 'nis', 'name', 'gender', 'status')
            ->with([
                'parent' => fn($query) => $query->select('id', 'name'),
                'class' => fn($query) => $query->select('id', 'name')
            ]);

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

    public function datatableSet(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');
        $selected = (empty($request->selected)) ? [] : $request->selected;

        $student = Student::select('id', 'id_class', 'id_asrama', 'id_halaqah', 'nis', 'name', 'status')
            ->with([
                'class' => fn($query) => $query->select('id', 'name'),
                'asrama' => fn($query) => $query->select('id', 'name'),
                'halaqah' => fn($query) => $query->select('id', 'name'),
            ]);

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
                    ->orWhereHas('asrama', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('halaqah', function ($q) use ($search) {
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
            $push['status_badge'] = $s->status_badge;
            $push['checked'] = (in_array($s->id, $selected)) ? ' checked' : '';

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

    public function datatableSetExcul(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $student = Student::select('id', 'nis', 'name', 'gender', 'exculs')
            ->whereIdClass($request->class);

        $student_count = $student->count();

        if (empty($search))
            $student_filter = $student;
        else {
            $student_filter = $student->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('nis', 'like', '%' . $search . '%');
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
            $push['exculs'] = $s->excul_list;

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

    public function datatableChange(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $student = Student::select('id', 'id_parent', 'id_class', 'nis', 'name', 'gender', 'status')
            ->with([
                'parent' => fn($query) => $query->select('id', 'name'),
                'class' => fn($query) => $query->select('id', 'name')
            ])
            ->whereIdClass($request->class);

        $student_count = $student->count();

        if (empty($search))
            $student_filter = $student;
        else {
            $student_filter = $student->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('nis', 'like', '%' . $search . '%')
                    ->orWhereHas('class', function ($q) use ($search) {
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

    public function datatableHistoryDisplacement(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $history = StudentDisplacementHistory::select('id', 'id_student', 'before_class_id', 'before_nis', 'after_class_id', 'after_nis',
                'created_at', 'created_by')
            ->with([
                'classBefore' => fn($query) => $query->select('id', 'name'),
                'classAfter' => fn($query) => $query->select('id', 'name'),
                'creator' => fn($query) => $query->select('id', 'name'),
            ])
            ->whereIdStudent($request->student);

        $history_count = $history->count();

        if (empty($search))
            $history_filter = $history;
        else {
            $history_filter = $history->where(function ($query) use ($search) {
                $query->whereHas('classBefore', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('classAfter', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $history_count_filter = $history_filter->count();
        $history_data = $history_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $response = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $history_count,
            'recordsFiltered' => $history_count_filter,
            'data' => $history_data
        ];

        return response()->json($response);
    }

    public function create()
    {
        $educations = Common::option('education_level');
        $religions = Common::option('religion');
        $genders = Common::option('gender');
        $yesno = Common::option('yesno');
        $activation = Common::option('activation');
        $parents = Parents::select('id', 'name')->orderBy('name')->pluck('name', 'id');
        $asramas = Asrama::select('id', 'name')->orderBy('name')->pluck('name', 'id');
        $halaqahs = Halaqah::select('id', 'name')->orderBy('name')->pluck('name', 'id');
        $exculs = Excul::select('id', 'name')->orderBy('name')->pluck('name', 'id');

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'educations' => $educations,
            'religions' => $religions,
            'genders' => $genders,
            'yesno' => $yesno,
            'parents' => $parents,
            'asramas' => $asramas,
            'halaqahs' => $halaqahs,
            'exculs' => $exculs,
            'activation' => $activation,
        ]);
    }

    public function set()
    {
        $educations = Common::option('education_level');
        $asramas = Asrama::select('id', 'name')->orderBy('name')->pluck('name', 'id');
        $halaqahs = Halaqah::select('id', 'name')->orderBy('name')->pluck('name', 'id');

        return view($this->path . 'set', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'educations' => $educations,
            'asramas' => $asramas,
            'halaqahs' => $halaqahs,
        ]);
    }

    public function setExcul()
    {
        $educations = Common::option('education_level');
        $exculs = Excul::select('id', 'name')->orderBy('name')->pluck('name', 'id');

        return view($this->path . 'set-excul', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'educations' => $educations,
            'exculs' => $exculs,
        ]);
    }

    public function change()
    {
        $educations = Common::option('education_level');

        return view($this->path . 'change', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'educations' => $educations,
        ]);
    }

    public function store(StudentRequest $request)
    {
        $filename_hashed = '';
        $extension = '';

        DB::transaction(function () use ($request, &$filename_hashed, &$extension) {
            $merge = [
                'spp' => (empty($request->spp)) ? null : $request->spp
            ];

            if (!empty($request->file('photo'))) {
                $extension = $request->file('photo')->extension();
                $filename = 'student-' . date("YmdHis") . '.' . $extension;
                $filename_hashed = Upload::generateFilename();

                $attachment = Attachment::create([
                    'filename' => $filename,
                    'filename_hashed' => $filename_hashed,
                    'type' => $request->file('photo')->getClientMimeType(),
                    'extension' => $extension,
                    'path' => Upload::getPath()
                ]);

                $merge['file_photo'] = $attachment->id;
            }

            $request->merge($merge);
            Student::create($request->all());
        });

        if (!empty($filename_hashed))
            Upload::image($request->file('photo'), $filename_hashed);

        return Redirect::route('academic.student.index')->with('success', __('message.create_success', ['label' => __($this->title)]));
    }

    public function storeSet(StudentSetRequest $request)
    {
        $student = $request->student;

        foreach ($student as $s) {
            if (empty($s))
                continue;

            $update = [];

            if (!empty($request->class))
                $update['id_class'] = $request->class;

            if (!empty($request->asrama))
                $update['id_asrama'] = $request->asrama;

            if (!empty($request->halaqah))
                $update['id_halaqah'] = $request->halaqah;

            Student::whereId($s)->update($update);
        }

        $response = [
            'status' => true,
            'message' => __('message.update_success', ['label' => __($this->title)])
        ];

        return response()->json($response);
    }

    public function storeSetExcul(StudentExculRequest $request)
    {
        $student = $request->student;
        $exculs = $request->exculs;

        foreach ($student as $s) {
            if (empty($s))
                continue;

            Student::whereId($s)->update([
                'exculs' => $exculs,
            ]);
        }

        $response = [
            'status' => true,
            'message' => __('message.update_success', ['label' => __($this->title)])
        ];

        return response()->json($response);
    }

    public function storeChange(StudentChangeRequest $request)
    {
        $student = $request->student;
        $nis = $request->nis;
        $class = $request->to_class;
        $error = false;

        foreach ($student as $index => $s) {
            if (empty($nis[$index])) {
                $error = __('validation.required', ['attribute' => __('label.nis')]);
                break;
            }
        }

        if ($error == false) {
            DB::transaction(function() use($student, $nis, $class) {
                foreach ($student as $index => $s) {
                    $student = Student::select('id', 'id_class', 'nis')->whereId($s)->first();

                    StudentDisplacementHistory::create([
                        'id_student' => $student->id,
                        'before_class_id' => $student->id_class,
                        'before_nis' => $student->nis,
                        'after_class_id' => $class,
                        'after_nis' => $nis[$index],
                    ]);

                    $student->update([
                        'id_class' => $class,
                        'nis' => $nis[$index],
                    ]);
                }
            });

            $response = [
                'status' => true,
                'message' => __('message.update_success', ['label' => __($this->title)])
            ];
        } else {
            $response = [
                'status' => false,
                'message' => $error
            ];
        }

        return response()->json($response);
    }

    public function edit(Student $student)
    {
        if (Auth::user()->role == UserRole::OrangTua)
            return $this->editParent($student);

        $educations = Common::option('education_level');
        $religions = Common::option('religion');
        $genders = Common::option('gender');
        $yesno = Common::option('yesno');
        $activation = Common::option('activation');
        $parents = Parents::select('id', 'name')->orderBy('name')->pluck('name', 'id');
        $asramas = Asrama::select('id', 'name')->orderBy('name')->pluck('name', 'id');
        $halaqahs = Halaqah::select('id', 'name')->orderBy('name')->pluck('name', 'id');
        $exculs = Excul::select('id', 'name')->orderBy('name')->pluck('name', 'id');

        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'student' => $student,
            'educations' => $educations,
            'religions' => $religions,
            'genders' => $genders,
            'yesno' => $yesno,
            'parents' => $parents,
            'asramas' => $asramas,
            'halaqahs' => $halaqahs,
            'exculs' => $exculs,
            'activation' => $activation,
        ]);
    }

    private function editParent($student)
    {
        $religions = Common::option('religion');
        $genders = Common::option('gender');

        return view($this->path . 'edit-parent', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'student' => $student,
            'religions' => $religions,
            'genders' => $genders,
        ]);
    }

    public function update(StudentRequest $request, Student $student) {
        $filename_hashed = '';
        $extension = '';
        $attachment_old = Attachment::find($student->file);

        DB::transaction(function () use ($request, $student, &$filename_hashed, &$extension) {
            $merge = [
                'spp' => (empty($request->spp)) ? null : $request->spp
            ];

            if (!empty($request->file('photo'))) {
                $extension = $request->file('photo')->extension();
                $filename = 'student-' . date("YmdHis") . '.' . $extension;
                $filename_hashed = Upload::generateFilename();

                $attachment = Attachment::create([
                    'filename' => $filename,
                    'filename_hashed' => $filename_hashed,
                    'type' => $request->file('photo')->getClientMimeType(),
                    'extension' => $extension,
                    'path' => Upload::getPath()
                ]);

                $merge['file_photo'] = $attachment->id;
            }

            $request->merge($merge);
            $student->update($request->all());
        });

        if (!empty($filename_hashed)) {
            Upload::image($request->file('photo'), $filename_hashed);

            if (!empty($attachment_old))
                $attachment_old->delete();
        }

        return Redirect::route('academic.student.index')->with('success',__('message.update_success', ['label' => __($this->title)]));
    }

    public function updateParent(StudentParentRequest $request, Student $student)
    {
        $request->merge(['birthdate' => date('Y-m-d', strtotime($request->birthdate))]);
        $student->update($request->all());

        return Redirect::route('academic.student.show', $student->encrypted_id)->with('success', __('message.update_success', ['label' => __($this->title)]));
    }

    public function destroy(Student $student)
    {
        $student->delete();

        $response = [
            'status' => true,
            'message' => __('message.delete_success', ['label' => __($this->title)])
        ];

        return response()->json($response);
    }

    // Get Student Data for jQueri UI Autocomplete
    // Used by :
    // - resources/views/backend/finance/transaction/bill/index.blade.php
    // - resources/views/backend/finance/savings/deposit.blade.php
    public function getAutocomplete(Request $request)
    {
        $students = [];
        $student = Student::select('id', 'id_class', 'nis', 'name')
            ->with(['class' => fn($query) => $query->select('id', 'name')])
            ->where('nis', 'like', '%' . $request->term . '%')
            ->orWhere('name', 'like', '%' . $request->term . '%')
            ->orderBy('name')
            ->limit(50)
            ->get();

        foreach ($student as $s) {
            array_push($students, [
                'id' => $s->id,
                'label' => $s->nis . ' - ' . $s->name . ' - ' . __('label.class') . ' ' . $s->class->name,
                'value' => $s->nis . ' - ' . $s->name
            ]);
        }

        return response()->json($students);
    }
}
