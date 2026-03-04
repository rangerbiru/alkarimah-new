<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\ClassHourDetail;
use App\Models\ClassHours;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\Employee;
use App\Models\Subject;
use Illuminate\Http\Request;

class ClassScheduleController extends Controller
{
    private $title = 'label.manage_lesson_schedule';
    private $icon = 'bx bxs-calendar';
    private $path = 'backend.academic.class.class-schedule.';
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $count = Classroom::count();
        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count
        ]);
    }

    public function create()
    {
        $class = Classroom::select('id', 'name')->get();
        $employee = Employee::select('id', 'name')->get();
        $subject = Subject::select('id', 'name')->get();
        $classHour = ClassHours::select('id', 'name')->get();

        return view($this->path . 'create', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'class' => $class,
            'employee' => $employee,
            'subject' => $subject,
            'classHour' => $classHour
        ]);
    }


    public function manageSchedule($id)
    {
        $class = Classroom::findOrFail($id);

        // Ambil subject yang sesuai dengan level_education kelas
        $subject = Subject::select('id', 'name', 'level_education')
            ->where('level_education', $class->level_education)
            ->get();

        $teacher = Employee::select('id', 'name')->get();

        $availableDays = ClassHourDetail::whereHas('classHour', fn($q) => $q->where('id_class', $id))
            ->pluck('day')
            ->unique()
            ->values()
            ->toArray();

        $dayOrder = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
        usort($availableDays, function ($a, $b) use ($dayOrder) {
            $posA = array_search($a, $dayOrder);
            $posB = array_search($b, $dayOrder);
            return $posA <=> $posB;
        });

        return view($this->path . 'manage-schedule', [
            'title' => __($this->title) . ' - ' . $class->name,
            'icon' => $this->icon,
            'class' => $class,
            'subject' => $subject,
            'teacher' => $teacher,
            'availableDays' => $availableDays,
        ]);
    }

    public function saveManageSchedule(Request $request, $classId)
    {
        $request->validate([
            'schedule' => 'array',
            'schedule.*.*.subject' => 'nullable|exists:subject,id',
            'schedule.*.*.teacher' => 'nullable|exists:employee,id',
        ]);

        foreach ($request->schedule as $jpNumber => $days) {
            foreach ($days as $day => $data) {
                $detail = ClassHourDetail::whereHas('classHour', function ($q) use ($classId) {
                    $q->where('id_class', $classId);
                })
                    ->where('jp_number', $jpNumber)
                    ->where('day', $day)
                    ->first();

                if ($detail) {
                    $detail->update([
                        'id_subject'  => $data['subject'] ?? null,
                        'id_employee' => $data['teacher'] ?? null,
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Jadwal berhasil diperbarui!');
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

    public function datatableManageSchedule(Request $request, $id)
    {
        $search = $request->input('search')['value'];
        $limit = $request->input('length');
        $start = $request->input('start');

        // Ambil semua ClassHourDetail untuk kelas ini
        $query = ClassHourDetail::whereHas('classHour', fn($q) => $q->where('id_class', $id))
            ->select(
                'id',
                'label',
                'start_time',
                'end_time',
                'day',
                'jp_number',
                'id_subject',
                'id_employee'
            );



        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('label', 'like', "%{$search}%")
                    ->orWhereHas('classHour', function ($sub) use ($search) {
                        $sub->where('label', 'like', "%{$search}%")
                            ->orWhere('day', 'like', "%{$search}%")
                            ->orWhere('start_time', 'like', "%{$search}%")
                            ->orWhere('end_time', 'like', "%{$search}%");
                    });
            });
        }



        // Ambil data
        $details = $query->get();

        // Kelompokkan berdasarkan jp_number
        $grouped = $details->groupBy('jp_number')->map(function ($items) {
            $first = $items->first();
            return [
                'jp_number' => $first->jp_number,
                'label' => $first->label,
                'start_time' => $first->start_time,
                'end_time' => $first->end_time,
                'days' => $items->keyBy('day')->toArray()
            ];
        });

        // Urutkan berdasarkan jp_number
        $sorted = $grouped->sortBy(function ($item) {
            if (is_numeric($item['jp_number'])) {
                return (int) $item['jp_number'];
            }
            return match ($item['jp_number']) {
                'istirahat_1' => 99,
                'istirahat_2' => 199,
                'sholat_dzuhur' => 299,
                default => 999
            };
        })->values();

        // Pagination manual
        $paginated = $sorted->slice($start, $limit);

        $recordsTotal = $paginated->count();

        $recordsFiltered = $paginated->count();

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $paginated->toArray()
        ]);
    }

    // public function datatableManageSchedule(Request $request, $id)
    // {
    //     $search = $request->input('search')['value'];
    //     $limit = $request->input('length');
    //     $start = $request->input('start');

    //     $query = ClassHourDetail::with('classHour');

    //     $query->whereHas('classHour', fn($q) => $q->where('id_class', $id));

    //     if (!empty($search)) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('label', 'like', "%{$search}%")
    //                 ->orWhereHas('classHour', function ($sub) use ($search) {
    //                     $sub->where('label', 'like', "%{$search}%");
    //                 });
    //         });
    //     }



    //     $data = $query->skip($start)
    //         ->take($limit)
    //         ->select('jp_number', 'label', 'start_time', 'end_time')
    //         ->orderBy('jp_number', 'asc')
    //         ->distinct()
    //         ->get();

    //     $recordsFiltered = $data->count();
    //     $recordsTotal = $data->count();

    //     // $allDetails = ClassHourDetail::whereHas('classHour', fn($q) => $q->where('id_class', $id))
    //     // ->select('jp_number', 'label', 'start_time', 'end_time')
    //     // ->distinct()
    //     // ->get();

    //     return response()->json([
    //         'draw' => (int) $request->input('draw'),
    //         'recordsTotal' => $recordsTotal,
    //         'recordsFiltered' => $recordsFiltered,
    //         'data' => $data
    //     ]);
    // }
}