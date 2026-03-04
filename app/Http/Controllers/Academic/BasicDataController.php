<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\BasicData;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\Subject;
use Illuminate\Http\Request;

class BasicDataController extends Controller
{
    private $title = 'label.basic_data';
    private $icon = 'bx bxs-book-content';
    private $path = 'backend.academic.basic-data.';

    public function index()
    {
        $count = BasicData::count();
        $subject = BasicData::with('teacher', 'subject', 'class', 'class.students')->get();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count,
            'subject' => $subject
        ]);
    }

    public function create()
    {
        $employee = Employee::with('user')
            ->whereHas('user', function ($query) {
                $query->whereIn('role', ['pegawai', 'admin']);
            })
            ->select('id', 'name')
            ->orderBy('id')
            ->get();

        $subject = Subject::select('id', 'name')->get();

        $class = Classroom::select('id', 'name')->get();

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'employee' => $employee,
            'subject' => $subject,
            'class' => $class
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_teacher' => 'required',
            'id_subject' => 'required',
            'id_class' => 'required'
        ]);

        $data = $request->all();
        BasicData::create($data);
        return redirect()->route('academic.basic.index')->with('success', 'Data berhasil disimpan');
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? '';
        $limit = $request->input('length') ?? 10;
        $start = $request->input('start') ?? 0;

        // Query utama
        $query = BasicData::with(['teacher', 'subject', 'class'])
            ->select('id', 'id_teacher', 'id_subject', 'id_class');

        // Filter pencarian
        if (!empty($search)) {
            $query->whereHas('teacher', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%");
            })
                ->orWhereHas('subject', function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%");
                })
                ->orWhereHas('class', function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%");
                });
        }

        // Hitung total sebelum pagination
        $recordsTotal = BasicData::count();
        $recordsFiltered = $query->count();

        // Pagination + Urutan
        $data = $query->orderBy('created_at', 'desc')
            ->skip($start)
            ->take($limit)
            ->get();

        // Format data agar rapi untuk frontend
        $result = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'teacher' => $item->teacher->name ?? '-',
                'subject' => $item->subject->name ?? '-',
                'class' => $item->class->name ?? '-',
                'students_count' => $item->class->students->count() ?? 0,
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $result,
        ]);
    }
}
