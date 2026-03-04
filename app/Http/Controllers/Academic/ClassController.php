<?php

namespace App\Http\Controllers\Academic;

use App\Enums\EducationLevel;
use App\Enums\UserRole;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClassRequest;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    private $title = 'label.class';
    private $icon = 'bx bx-building';
    private $path = 'backend.academic.class.';

    public function index()
    {
        $count = Classroom::count();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count,
        ]);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        $class = Classroom::select('id', 'id_wali_kelas', 'name', 'level_education', 'level_class')
            ->with(['waliKelas' => fn($query) => $query->select('id', 'name')]);

        $class_count = $class->count();

        if (empty($search))
            $class_filter = $class;
        else {
            $class_filter = $class->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('level_education', 'like', '%' . $search . '%')
                    ->orWhere('level_class', 'like', '%' . $search . '%');
            });
        }

        $class_count_filter = $class_filter->count();
        $class_data = $class_filter->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        $class_arr = [];

        foreach ($class_data as $b) {
            $push = $b->toArray();
            $push['encrypted_id'] = $b->encrypted_id;
            $push['level_education'] = strtoupper($b->level_education->value);

            array_push($class_arr, $push);
        }

        $response = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $class_count,
            'recordsFiltered' => $class_count_filter,
            'data' => $class_arr
        ];

        return response()->json($response);
    }

    public function create()
    {
        $educations = Common::option('education_level');
        $wali_kelas = User::select('id', 'name')->whereRole(UserRole::WaliKelas)->orderBy('name')->pluck('name', 'id');

        return view($this->path . 'create', [
            'title' =>  __($this->title),
            'icon' => $this->icon,
            'educations' => $educations,
            'wali_kelas' => $wali_kelas
        ]);
    }

    public function store(ClassRequest $request)
    {
        Classroom::create($request->all());

        return redirect()->route('academic.class.index')->with('success', __('message.create_success', ['label' => __($this->title)]));
    }

    public function edit(Classroom $class)
    {
        $educations = Common::option('education_level');
        $wali_kelas = User::select('id', 'name')->whereRole(UserRole::WaliKelas)->orderBy('name')->pluck('name', 'id');

        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'class' => $class,
            'educations' => $educations,
            'wali_kelas' => $wali_kelas
        ]);
    }


    public function update(ClassRequest $request, Classroom $class)
    {
        $class->update($request->all());

        return redirect()->route('academic.class.index')->with('success', __('message.update_success', ['label' => __($this->title)]));
    }

    public function destroy(Classroom $class)
    {
        $class->delete();

        return response()->json(['message' => __('message.delete.success', ['label' => $this->title])]);
    }

    public function getOption(Request $request)
    {
        $options = '<option value=""></option>';
        $class = Classroom::select('id', 'name')->whereLevelEducation($request->level)->orderBy('name')->get();

        foreach ($class as $c)
            $options .= '<option value="' . $c->id . '">' . $c->name . '</option>';

        return response()->json(['option' => $options]);
    }

    public function getOptionLevel(Request $request)
    {
        switch ($request->level) {
            case EducationLevel::SD->value:
                $class = [
                    '1' => '1 SD',
                    '2' => '2 SD',
                    '3' => '3 SD',
                    '4' => '4 SD',
                    '5' => '5 SD',
                    '6' => '6 SD',
                ];
                break;

            case EducationLevel::SMP->value:
                $class = [
                    '1' => '1 SMP',
                    '2' => '2 SMP',
                    '3' => '3 SMP',
                ];
                break;

            default:
                $class = [
                    '1' => '1 SMA',
                    '2' => '2 SMA',
                    '3' => '3 SMA',
                ];
        }

        $options = '<option value=""></option>';

        foreach ($class as $id => $name)
            $options .= '<option value="' . $id . '">' . $name . '</option>';

        return response()->json(['option' => $options]);
    }

    public function getOptionLevelClass(Request $request)
    {
        $options = '<option value=""></option>';
        $class = Classroom::select('id', 'name')->whereLevelClass($request->level)->orderByRaw('CAST(name AS UNSIGNED)')->get();

        foreach ($class as $c)
            $options .= '<option value="' . $c->id . '">' . $c->name . '</option>';

        return response()->json(['option' => $options]);
    }
}
