<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\ClassHourDetail;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    private $title = 'label.subject';
    private $icon = 'bx bxs-book-content';
    private $path = 'backend.academic.subject.';

    public function index()
    {
        $count = Subject::count();
        $subject = Subject::all();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count,
            'subject' => $subject
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
            'name' => 'required',
            'lesson_hours' => 'required',
            'level_education' => 'required'
        ]);

        Subject::create($request->all());

        return redirect()->route('academic.subject.index')->with('success', 'Data berhasil disimpan');
    }

    public function edit($id)
    {
        $subject = Subject::findOrFail($id);

        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'subject' => $subject
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'lesson_hours' => 'required',
            'level_education' => 'required'
        ]);

        $subject = Subject::findOrFail($id);
        $subject->update($request->all());

        return redirect()->route('academic.subject.index')->with('success', 'Data berhasil diperbarui');
    }

    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        $classDetail = ClassHourDetail::where('id_subject', $subject->id)->get();

        foreach ($classDetail as $item) {
            $item->delete();
        }
        $subject->delete();
        return redirect()->route('academic.subject.index')->with('success', 'Data berhasil dihapus');
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? '';
        $limit = $request->input('length') ?? 10;
        $start = $request->input('start') ?? 0;

        $query = Subject::select('id', 'name', 'level_education', 'lesson_hours');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('level_education', 'like', "%{$search}%")
                    ->orWhere('lesson_hours', 'like', "%{$search}%");
            });
        }

        $recordsTotal = Subject::count();
        $recordsFiltered = $query->count();

        $data = $query->orderBy('created_at', 'desc')
            ->skip($start)
            ->take($limit)
            ->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }
}