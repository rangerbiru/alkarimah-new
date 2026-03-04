<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ClassHourDetail;
use App\Models\ClassHours;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassHoursController extends Controller
{
    private $title = 'label.manage_class_hours';
    private $icon = 'bx bx-time-five';
    private $path = 'backend.academic.class.class-hours.';
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $count = ClassHours::count();
        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count
        ]);
    }

    public function create()
    {
        $class = Classroom::select('id', 'name')->get();
        $branch = Branch::select('id', 'name')->get();

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'class' => $class,
            'branch' => $branch
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_class'   => 'required',
            'id_branch'  => 'required',
            'name'       => 'required|string',
            'start_time' => 'nullable|date_format:H:i',
            'end_time'   => 'nullable|date_format:H:i',
            'jp_total'   => 'nullable|integer',
            'jp_duration' => 'nullable|integer',
            'day'        => 'required|array',
            'day.*'      => 'in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
        ]);

        $lessonHours = $request->lesson_hours ?? [];

        DB::beginTransaction();

        try {
            $classHour = ClassHours::create([
                'id_class'    => $request->id_class,
                'id_branch'   => $request->id_branch,
                'name'        => $request->name,
                'jp_count'    => $request->jp_total ?? 0,
                'jp_duration' => $request->jp_duration ?? 0,
                'start_time'  => $request->start_time,
                'end_time'    => $request->end_time,
            ]);

            $selectedDays = $request->day;

            if (!empty($lessonHours) && is_array($lessonHours)) {

                foreach ($selectedDays as $day) {
                    foreach ($lessonHours as $item) {

                        if (empty($item['jp']) && empty($item['start_time']) && empty($item['end_time'])) {
                            continue;
                        }

                        ClassHourDetail::create([
                            'id_class_hour' => $classHour->id,
                            'day'           => $day,
                            'jp_number'     => $item['jp'] ?? null,
                            'label'         => isset($item['jp']) && is_numeric($item['jp'])
                                ? "Jam ke {$item['jp']}"
                                : (isset($item['jp']) ? ucfirst(str_replace('_', ' ', $item['jp'])) : null),
                            'start_time'    => $item['start_time'] ?? null,
                            'end_time'      => $item['end_time'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('academic.class-hours.index')
                ->with('success', 'Jam pelajaran berhasil disimpan!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['msg' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }



    public function edit($id)
    {
        $classHour = ClassHours::with('branch', 'class')->findOrFail($id);
        $branch = Branch::select('id', 'name')->get();
        $class = Classroom::select('id', 'name')->get();

        $selectedDays = ClassHourDetail::where('id_class_hour', $id)
            ->pluck('day')
            ->unique()
            ->values();

        $firstDay = $selectedDays->first();
        $details = collect();

        if ($firstDay) {
            $details = ClassHourDetail::where('id_class_hour', $id)
                ->where('day', $firstDay)
                // ->orderByRaw("CAST(jp_number AS UNSIGNED) ASC, jp_number ASC")
                ->get();
        }

        return view($this->path . 'edit', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'classHour' => $classHour,
            'branch' => $branch,
            'class' => $class,
            'details' => $details,
            'selectedDays' => $selectedDays->toArray(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_class'   => 'required',
            'id_branch'  => 'required',
            'name'       => 'required',
            'start_time' => 'required',
            'end_time'   => 'required',
            'jp_total'   => 'required|integer',
        ]);

        $classHour = ClassHours::findOrFail($id);

        $classHour->update([
            'id_class'   => $request->id_class,
            'id_branch'  => $request->id_branch,
            'name'       => $request->name,
            'jp_count'   => $request->jp_total,
            'start_time' => $request->start_time,
            'end_time'   => $request->end_time,
        ]);

        // Ambil SEMUA detail lama, termasuk kolom tambahan
        $existingDetails = ClassHourDetail::where('id_class_hour', $classHour->id)
            ->get()
            ->keyBy(function ($detail) {
                return $detail->day . '|' . $detail->jp_number;
            }); // Buat key unik: "senin|1", "senin|istirahat_1", dll

        $newDetails = collect();
        $days = $request->day ?? [];

        // Bangun daftar entri baru dari form
        foreach ($request->lesson_hours ?? [] as $item) {
            if (!isset($item['jp']) || !$item['jp'] || !isset($item['start_time']) || !isset($item['end_time'])) {
                continue;
            }

            foreach ($days as $day) {
                $key = $day . '|' . $item['jp'];

                // Coba ambil data lama (termasuk id_subject, id_employee)
                $existing = $existingDetails->get($key);

                $label = is_numeric($item['jp'])
                    ? "Jam ke {$item['jp']}"
                    : ucfirst(str_replace('_', ' ', $item['jp']));

                $newDetails->push([
                    'id_class_hour' => $classHour->id,
                    'day'           => $day,
                    'jp_number'     => $item['jp'],
                    'label'         => $label,
                    'start_time'    => $item['start_time'],
                    'end_time'      => $item['end_time'],
                    'id_subject'    => $existing ? $existing->id_subject : null,
                    'id_employee'   => $existing ? $existing->id_employee : null,
                    // tambahkan field lain jika ada
                ]);
            }
        }

        // Ambil key dari data baru
        $newKeys = $newDetails->map(fn($d) => $d['day'] . '|' . $d['jp_number'])->values();

        // HAPUS hanya yang TIDAK ADA di form
        ClassHourDetail::where('id_class_hour', $classHour->id)
            ->whereNotIn(DB::raw("CONCAT(day, '|', jp_number)"), $newKeys)
            ->delete();

        // UPDATE atau INSERT
        foreach ($newDetails as $detail) {
            $key = $detail['day'] . '|' . $detail['jp_number'];
            $existing = $existingDetails->get($key);

            if ($existing) {
                // Hanya update start_time & end_time (dan label jika perlu)
                $existing->update([
                    'start_time' => $detail['start_time'],
                    'end_time'   => $detail['end_time'],
                    'label'      => $detail['label'],
                    // JANGAN update id_subject/id_employee!
                ]);
            } else {
                // Insert baru
                ClassHourDetail::create($detail);
            }
        }

        return redirect()
            ->route('academic.class-hours.index')
            ->with('success', 'Jam pelajaran berhasil diperbarui!');
    }





    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? '';
        $limit = $request->input('length') ?? 10;
        $start = $request->input('start') ?? 0;

        $query = ClassHours::with(['branch', 'class']);

        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhereHas('class', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('branch', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('class', function ($q) use ($search) {
                    $q->where('level_education', 'like', "%{$search}%");
                });
        }

        $recordsTotal = ClassHours::count();
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

    public function destroy($id)
    {
        ClassHourDetail::where('id_class_hour', $id)->delete();
        ClassHours::destroy($id);
        return back()->with('success', 'Data berhasil dihapus');
    }
}