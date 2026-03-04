<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceGroup;
use App\Models\AttendanceGroupMembers;
use App\Models\CommitteeActivity;
use App\Models\CommitteeDocument;
use App\Models\CommitteeMember;
use App\Models\Employee;
use App\Models\EmployeeActivity;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommitteeActivityController extends Controller
{
    private $title = 'label.committee_activity';
    private $path = 'backend.employee.committee-activity.';
    private $icon = 'bx bxs-user-circle';

    public function index(CommitteeActivity $committeeActivity)
    {
        $activity = $committeeActivity->with([
            'employees:id,name,task_main',
            'photos:id,committee_activity_id,file_path,file_name',
            'skDocuments:id,committee_activity_id,file_path,file_name',
            'beritaAcara:id,committee_activity_id,file_path,file_name',
        ])->get();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'activity' => $activity
        ]);
    }

    // public function show(CommitteeActivity $committeeActivity)
    // {
    //     // Load relasi
    //     $activity = $committeeActivity->load([
    //         'employees:id,name,task_main',
    //         'photos:id,committee_activity_id,file_path,file_name',
    //         'skDocuments:id,committee_activity_id,file_path,file_name',
    //         'beritaAcara:id,committee_activity_id,file_path,file_name',
    //     ]);

    //     return response()->json([
    //         'html' => view($this->path . 'modal', compact('activity'))->render()
    //     ]);
    // }

    public function create()
    {
        $employee = Auth::user()->employee;

        $activity = EmployeeActivity::with([
            'position' => fn($query) => $query->select('id', 'name')
        ])->select('id', 'activity_name', 'id_position')->get();

        $relatedFields = $activity
            ->pluck('position')
            ->filter()
            ->unique('id')
            ->pluck('name', 'name');

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'employee' => $employee,
            'activity' => $activity,
            'relatedFields' => $relatedFields
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'activity_date' => 'required|date',
            'related_field' => 'required|string',
            'activity_name' => 'required|string',
            'location' => 'nullable|string',
            'activity_summary' => 'nullable|string',
            'employee_id' => 'required|string',
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'file|mimes:jpg,jpeg,png,gif,bmp,webp|max:5120',
            'sk' => 'nullable|array|max:5',
            'sk.*' => 'file|mimes:pdf,doc,docx|max:10240',
            'minutes' => 'nullable|array|max:5',
            'minutes.*' => 'file|mimes:pdf,doc,docx|max:10240',
        ], [
            'activity_date.required' => 'Tanggal aktivitas harus diisi.',
            'related_field.required' => 'Bidang terkait harus diisi.',
            'activity_name.required' => 'Nama aktivitas harus diisi.',
            'photos.*.file' => 'Foto harus berupa file.',
            'photos.*.mimes' => 'Foto harus berformat: jpg, jpeg, png, gif.',
            'photos.*.max' => 'Ukuran foto maksimal 5 MB.',
            'sk.file' => 'Surat keputusan harus berupa file.',
            'sk.mimes' => 'Surat keputusan harus berformat: pdf, doc, docx.',
            'sk.max' => 'Ukuran surat keputusan maksimal 10 MB.',
            'minutes.file' => 'Catatan harus berupa file.',
            'minutes.mimes' => 'Catatan harus berformat: pdf, doc, docx.',
            'minutes.max' => 'Ukuran catatan maksimal 10 MB.',
        ]);

        $employeeIds = array_filter(explode(',', $request->input('employee_id')), 'is_numeric');

        $responsibleEmployee = Auth::user()->employee;
        if (!in_array($responsibleEmployee->id, $employeeIds)) {
            $employeeIds[] = $responsibleEmployee->id;
        }

        $activity = CommitteeActivity::create([
            'id_responsible_person' => $responsibleEmployee->id,
            'responsible_person' => $responsibleEmployee->name,
            'activity_date' => $request->activity_date,
            'related_field' => $request->related_field,
            'activity_type' => 'kepanitiaan',
            'activity_name' => $request->activity_name,
            'location' => $request->location,
            'participant_count' => count($employeeIds),
            'activity_summary' => $request->activity_summary,
        ]);

        foreach ($employeeIds as $empId) {
            CommitteeMember::create([
                'committee_activity_id' => $activity->id,
                'employee_id' => $empId,
            ]);
        }

        $folderPath = "activity-documents/committee-{$activity->id}";

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $filename = 'Foto_' . Str::random(16) . '.' . $photo->getClientOriginalExtension();
                $path = $photo->storeAs($folderPath, $filename, 'public');
                CommitteeDocument::create([
                    'committee_activity_id' => $activity->id,
                    'file_path' => $path,
                    'file_name' => $photo->getClientOriginalName(),
                    'file_type' => 'photo',
                ]);
            }
        }

        if ($request->hasFile('sk')) {
            foreach ($request->file('sk') as $file) {
                $filename = 'SK_' . Str::random(16) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs($folderPath, $filename, 'public');
                CommitteeDocument::create([
                    'committee_activity_id' => $activity->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => 'sk',
                ]);
            }
        }

        if ($request->hasFile('minutes')) {
            foreach ($request->file('minutes') as $file) {
                $filename = 'BeritaAcara_' . Str::random(16) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs($folderPath, $filename, 'public');
                CommitteeDocument::create([
                    'committee_activity_id' => $activity->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => 'berita_acara',
                ]);
            }
        }

        return redirect()
            ->route('employee.committee-activity.index')
            ->with('success', 'Kegiatan kepanitiaan berhasil disimpan.');
    }

    public function edit(CommitteeActivity $committeeActivity)
    {
        $employee = Auth::user()->employee;

        $activity = EmployeeActivity::select('id', 'activity_name', 'id_position')->get();

        $relatedFields = $activity
            ->pluck('position')
            ->filter()
            ->unique('id')
            ->pluck('name', 'name');

        $existingEmployees = $committeeActivity->employees->map(function ($e) {
            return [
                'id' => (string) $e->id,
                'name' => $e->name,
                'task_main' => $e->task_main ?? '-',
            ];
        })->values();

        return view($this->path . 'edit', [
            'title' => __('Edit Kegiatan'),
            'icon' => $this->icon,
            'employee' => $employee,
            'activity' => $activity,
            'relatedFields' => $relatedFields,
            'committeeActivity' => $committeeActivity,
            'existingEmployees' => $existingEmployees,
        ]);
    }

    public function destroyDocument(CommitteeDocument $document)
    {
        if (!$document->committee_activity_id || !$document->committeeActivity) {
            return response()->json([
                'error' => 'Dokumen tidak terkait dengan kegiatan yang valid.'
            ], 400);
        }

        $activity = $document->committeeActivity;

        if ($activity->id_responsible_person !== Auth::user()->employee->id) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json(['success' => true]);
    }

    public function update(Request $request, CommitteeActivity $committeeActivity)
    {
        // Validasi
        $request->validate([
            'activity_date' => 'required|date',
            'related_field' => 'required|string',
            'activity_name' => 'required|string',
            'location' => 'nullable|string',
            'activity_summary' => 'nullable|string',
            'employee_id' => 'required|string',
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'file|mimes:jpg,jpeg,png,gif,bmp,webp|max:5120',
            'sk' => 'nullable|array|max:5',
            'sk.*' => 'file|mimes:pdf,doc,docx|max:10240',
            'minutes' => 'nullable|array|max:5',
            'minutes.*' => 'file|mimes:pdf,doc,docx|max:10240',
        ], [
            'activity_date.required' => 'Tanggal kegiatan harus diisi.',
            'related_field.required' => 'Bidang terkait harus diisi.',
            'activity_name.required' => 'Nama kegiatan harus diisi.',
            'employee_id.required' => 'Pegawai harus dipilih.',
            'photos.array' => 'Foto harus berupa array.',
            'photos.max' => 'Maksimal 10 foto.',
            'photos.*.file' => 'Foto harus berupa file.',
            'photos.*.mimes' => 'Foto harus berformat: jpg, jpeg, png, gif, bmp, webp.',
            'photos.*.max' => 'Ukuran foto maksimal 5 MB.',
            'sk.array' => 'Surat keputusan harus berupa array.',
            'sk.max' => 'Maksimal 5 file SK.',
            'sk.*.file' => 'Surat keputusan harus berupa file.',
            'sk.*.mimes' => 'Surat keputusan harus berformat: pdf, doc, docx.',
            'sk.*.max' => 'Ukuran surat keputusan maksimal 10 MB.',
            'minutes.array' => 'Berita acara harus berupa array.',
            'minutes.max' => 'Maksimal 5 file berita acara.',
            'minutes.*.file' => 'Berita acara harus berupa file.',
            'minutes.*.mimes' => 'Berita acara harus berformat: pdf, doc, docx.',
            'minutes.*.max' => 'Ukuran berita acara maksimal 10 MB.',
        ]);

        if (!$request->user()->employee) {
            return redirect()->back()->withErrors(['Pengguna tidak terdaftar sebagai pegawai.']);
        }

        $responsibleEmployee = $request->user()->employee;

        $employeeIds = collect(explode(',', $request->input('employee_id')))
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->values();

        if (!$employeeIds->contains($responsibleEmployee->id)) {
            $employeeIds->push($responsibleEmployee->id);
        }

        $committeeActivity->update([
            'id_responsible_person' => $responsibleEmployee->id,
            'responsible_person' => $responsibleEmployee->name,
            'activity_date' => $request->activity_date,
            'related_field' => $request->related_field,
            'activity_type' => 'kepanitiaan',
            'activity_name' => $request->activity_name,
            'location' => $request->location,
            'participant_count' => $employeeIds->count(),
            'activity_summary' => $request->activity_summary,
        ]);

        $committeeActivity->employees()->sync($employeeIds->all());

        $folderPath = "activity-documents/committee-{$committeeActivity->id}";

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $filename = 'Foto_' . Str::random(16) . '.' . $photo->getClientOriginalExtension();
                $path = $photo->storeAs($folderPath, $filename, 'public');

                CommitteeDocument::create([
                    'committee_activity_id' => $committeeActivity->id,
                    'file_path' => $path,
                    'file_name' => $photo->getClientOriginalName(),
                    'file_type' => 'photo',
                ]);
            }
        }

        if ($request->hasFile('sk')) {
            foreach ($request->file('sk') as $file) {
                $filename = 'SK_' . Str::random(16) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs($folderPath, $filename, 'public');

                CommitteeDocument::create([
                    'committee_activity_id' => $committeeActivity->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => 'sk',
                ]);
            }
        }

        if ($request->hasFile('minutes')) {
            foreach ($request->file('minutes') as $file) {
                $filename = 'BeritaAcara_' . Str::random(16) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs($folderPath, $filename, 'public');

                CommitteeDocument::create([
                    'committee_activity_id' => $committeeActivity->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => 'berita_acara',
                ]);
            }
        }

        return redirect()
            ->route('employee.committee-activity.index')
            ->with('success', 'Kegiatan berhasil diperbarui.');
    }

    public function destroy(CommitteeActivity $committeeActivity)
    {
        if ($committeeActivity->id_responsible_person !== Auth::user()->employee->id) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        foreach ($committeeActivity->documents as $document) {
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
                Storage::disk('public')->deleteDirectory("activity-documents/committee-{$committeeActivity->id}");
            }
            $document->delete();
        }

        $committeeActivity->committeeMembers()->delete();

        $committeeActivity->delete();

        return redirect()->route('employee.committee-activity.index')->with('success', 'Kegiatan berhasil dihapus.');
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? null;
        $limit  = $request->input('length') ?? 10;
        $start  = $request->input('start') ?? 0;

        $query = CommitteeActivity::with([
            'employees' => fn($query) => $query->select('name', 'phone', 'nip'),
            'committeeMembers' => fn($query) => $query->select('committee_activity_id', 'employee_id'),
        ]);

        $recordsTotal = $query->count();

        if (!empty($search)) {
            $query->where('activity_name', 'like', '%' . $search . '%')
                ->orWhere('activity_type', 'like', '%' . $search . '%');
        }

        $recordsFiltered = $query->count();

        $data = $query->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data
        ]);
    }

    public function dataEmployee(Request $request)
    {
        $search = $request->input('search')['value'] ?? null;
        $limit  = $request->input('length') ?? 10;
        $start  = $request->input('start') ?? 0;

        $query = Employee::select('id', 'name', 'nip', 'phone', 'task_main', 'gender');

        $recordsTotal = $query->count();

        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('gender', 'like', '%' . $search . '%')
                ->orWhere('task_main', 'like', '%' . $search . '%');;
        }

        $recordsFiltered = $query->count();

        $data = $query->limit($limit)
            ->offset($start)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data
        ]);
    }
}
