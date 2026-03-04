<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentViolations;
use App\Models\ViolationTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StudentViolationController extends Controller
{
    private $title = 'label.student_violation';
    private $icon = 'bx bxs-book-content';
    private $path = 'backend.academic.violation.';

    public function index()
    {
        $count = StudentViolations::count();
        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count
        ]);
    }

    public function create()
    {
        $status = [
            'draft' => 'Draft',
            'tabayyun' => 'Tabayyun',
            'disahkan' => 'Disahkan'
        ];
        return view($this->path . 'create', [
            'title' => __('Input Pelanggaran'),
            'icon' => $this->icon,
            'status' => $status
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:student,id',
            'violation_type_id' => 'required|exists:violation_types,id',
            'time' => 'required',
            'location' => 'required',
            'notes' => 'nullable|string',
            'proof' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
            'status' => 'required|in:draft,tabayyun,disahkan',
        ], [
            'student_id.required' => 'Siswa harus diisi',
            'student_id.exists' => 'Siswa tidak ditemukan',
            'violation_type_id.required' => 'Pelanggaran harus diisi',
            'violation_type_id.exists' => 'Pelanggaran tidak ditemukan',
            'date.date' => 'Format tanggal tidak valid',
            'time.required' => 'Waktu harus diisi',
            'location.required' => 'Lokasi harus diisi',
            'status.required' => 'Status harus diisi',
            'status.in' => 'Status tidak valid',
        ]);

        // Handle Upload File
        $filePath = null;
        if ($request->hasFile('proof')) {
            $filePath = $request->file('proof')->store('violations', 'public');
        }

        StudentViolations::create([
            'student_id' => $validated['student_id'],
            'violation_id' => $validated['violation_type_id'],
            'employee_id' => Auth::user()->employee->id,
            'date' => date('Y-m-d'),
            'time' => $validated['time'],
            'location' => $validated['location'],
            'notes' => $validated['notes'],
            'proof' => $filePath,
            'status' => $validated['status'],
        ]);

        return redirect()->route('academic.violation.index')
            ->with('success', 'Data pelanggaran berhasil disimpan.');
    }

    public function edit(StudentViolations $violation)
    {
        // Load relasi agar bisa diakses di view
        $violation->load(['student', 'violation', 'student.class', 'student.asrama']);

        return view($this->path . 'edit', [
            'title' => __('Edit Pelanggaran'),
            'icon' => $this->icon,
            'violation' => $violation
        ]);
    }

    public function update(Request $request, StudentViolations $violation)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:student,id',
            'violation_type_id' => 'required|exists:violation_types,id',
            'date' => 'required|date',
            'time' => 'required',
            'location' => 'required',
            'notes' => 'nullable|string',
            'proof' => 'nullable|file|mimes:jpg,png,jpeg,pdf|max:2048',
            'status' => 'required|in:draft,tabayyun,disahkan',
        ]);

        $filePath = $violation->proof;
        if ($request->hasFile('proof')) {
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            $filePath = $request->file('proof')->store('violations', 'public');
        }

        $violation->update([
            'student_id' => $validated['student_id'],
            'violation_id' => $validated['violation_type_id'],
            'date' => $validated['date'],
            'time' => $validated['time'],
            'location' => $validated['location'],
            'notes' => $validated['notes'],
            'proof' => $filePath,
            'status' => $validated['status'],
        ]);

        return redirect()->route('academic.violation.index')
            ->with('success', 'Data pelanggaran berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $violation = StudentViolations::findOrFail($id);
        $violation->delete();
        return redirect()->route('academic.violation.index')
            ->with('success', 'Data pelanggaran berhasil dihapus.');
    }

    // Endpoint untuk mendapatkan data siswa
    public function getStudents(Request $request)
    {
        $search = $request->get('q');
        $students = Student::with(['class', 'asrama'])
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'text' => "{$student->nis} - {$student->name}", // Format untuk Select2
                    'nis' => $student->nis,
                    'name' => $student->name,
                    'gender' => $student->genderName,
                    'class' => $student->class->name ?? '-',
                    'asrama' => $student->asrama->name ?? '-',
                ];
            });

        return response()->json(['results' => $students]);
    }

    // Endpoint untuk mendapatkan jenis pelanggaran berdasarkan Group
    public function getViolationTypes(Request $request)
    {

        $types = ViolationTypes::where('status', 'aktif')->get();

        return response()->json($types);
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search.value');
        $limit = $request->input('length', 10);
        $start = $request->input('start', 0);

        $query = StudentViolations::with([
            'student' => function ($q) {
                $q->select('id', 'name', 'nis', 'id_class', 'id_asrama', 'gender');
                $q->with(['class', 'asrama']);
            },
            'violation' => function ($q) {
                $q->select('id', 'code', 'group', 'description', 'points', 'impact_level');
            },
            'employee' => function ($q) {
                $q->select('id', 'name');
            },
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                })
                    ->orWhereHas('violation', function ($q) use ($search) {
                        $q->where('code', 'like', "%{$search}%")
                            ->orWhere('group', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
            });
        }

        $totalRecords = StudentViolations::count();
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
