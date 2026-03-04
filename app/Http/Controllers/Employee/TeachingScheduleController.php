<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AttendanceStudents;
use App\Models\ClassHourDetail;
use App\Models\ClassHours;
use App\Models\Student;
use App\Models\TeachingJournal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TeachingScheduleController extends Controller
{
    private $title = 'label.teaching_schedule';
    private $path = 'backend.employee.teaching-schedule.';
    private $icon = 'bx bx-book-reader';

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hariMap = [
            'monday' => 'senin',
            'tuesday' => 'selasa',
            'wednesday' => 'rabu',
            'thursday' => 'kamis',
            'friday' => 'jumat',
            'saturday' => 'sabtu',
            'sunday' => 'minggu',
        ];
        $today = $hariMap[strtolower(Carbon::now()->format('l'))];
        $todayDate = now()->toDateString();

        $rawSchedules = ClassHourDetail::with([
            'subject:id,name',
            'classHour:id,id_class',
            'classHour.class:id,name'
        ])
            ->where('id_employee', Auth::user()->employee->id)
            ->whereNotNull('id_subject')
            ->orderBy('day')
            ->orderByRaw("CAST(jp_number AS UNSIGNED), jp_number")
            ->get();

        $scheduleIds = $rawSchedules->pluck('id')->toArray();

        $attendedIds = AttendanceStudents::whereIn('id_class_hour_details', $scheduleIds)
            ->whereDate('date', $todayDate)
            ->pluck('id_class_hour_details')
            ->flip()
            ->toArray();

        $journalIds = TeachingJournal::whereIn('id_class_hour_details', $scheduleIds)
            ->whereDate('date', $todayDate)
            ->pluck('id_class_hour_details')
            ->flip()
            ->toArray();

        // Grouping...
        $grouped = $rawSchedules->groupBy(function ($item) {
            return implode('|', [
                $item->day,
                $item->classHour?->id_class ?? '0',
                $item->id_employee ?? '0',
                $item->id_subject ?? '0'
            ]);
        });

        $finalSchedules = collect();

        foreach ($grouped as $groupKey => $schedules) {
            $sorted = $schedules->sortBy(function ($s) {
                return is_numeric($s->jp_number) ? (int)$s->jp_number : 999;
            })->values();

            $blocks = $this->groupConsecutiveJPs($sorted);

            foreach ($blocks as $block) {
                if ($block->isEmpty()) continue;

                $first = $block->first();
                $last = $block->last();

                $finalSchedules->push((object)[
                    'id' => $first->id,
                    'subject' => $first->subject->name ?? '–',
                    'day' => $first->day,
                    'start_time' => $first->start_time,
                    'end_time' => $last->end_time,
                    'class' => $first->classHour?->class?->name ?? '–',
                    'is_today' => strtolower($first->day) === strtolower($today),
                    'jp_count' => $block->count(),
                    'jp_range' => is_numeric($first->jp_number) && is_numeric($last->jp_number)
                        ? ($first->jp_number == $last->jp_number ? "Jam {$first->jp_number}" : "Jam {$first->jp_number}–{$last->jp_number}")
                        : $first->label,
                    'is_attended' => isset($attendedIds[$first->id]),
                    'journal_filled' => isset($journalIds[$first->id]),
                ]);
            }
        }

        $hariUrutan = ['senin' => 1, 'selasa' => 2, 'rabu' => 3, 'kamis' => 4, 'jumat' => 5, 'sabtu' => 6, 'minggu' => 7];
        $finalSchedules = $finalSchedules->sortBy(function ($item) use ($today, $hariUrutan) {
            if ($item->is_today) return 0;
            return $hariUrutan[$item->day] ?? 999;
        })->values();

        // dd($finalSchedules);

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'teachingSchedule' => $finalSchedules,
        ]);
    }

    private function groupConsecutiveJPs(Collection $schedules)
    {
        $blocks = [];
        $currentBlock = collect();

        foreach ($schedules as $schedule) {
            if (!is_numeric($schedule->jp_number)) {
                // Jika bukan angka (misal: istirahat), akhiri blok
                if ($currentBlock->isNotEmpty()) {
                    $blocks[] = $currentBlock;
                    $currentBlock = collect();
                }
                continue;
            }

            $jp = (int)$schedule->jp_number;

            if ($currentBlock->isEmpty()) {
                $currentBlock->push($schedule);
            } else {
                $lastJp = (int)$currentBlock->last()->jp_number;
                if ($jp === $lastJp + 1) {
                    $currentBlock->push($schedule);
                } else {
                    // Ada gap → simpan blok lama, mulai baru
                    $blocks[] = $currentBlock;
                    $currentBlock = collect([$schedule]);
                }
            }
        }

        if ($currentBlock->isNotEmpty()) {
            $blocks[] = $currentBlock;
        }

        return $blocks;
    }

    public function show($id)
    {
        $detail = ClassHourDetail::with([
            'classHour.class:id,name',
            'subject:id,name',
            'teacher:id,name',
        ])
            ->where('id', $id)
            ->firstOrFail();

        $students = Student::where('id_class', $detail->classHour->class->id)->orderBy('name')->get();

        $today = now()->toDateString();
        $existingAttendance = AttendanceStudents::where('id_class_hour_details', $id)
            ->whereDate('date', $today)
            ->pluck('status', 'id_student')
            ->toArray();

        Carbon::setLocale('id');
        $todayDate = Carbon::now()->format('Y-m-d');
        $carbonDate = Carbon::createFromFormat('Y-m-d', $todayDate);
        $formattedDate = $carbonDate->isoFormat('dddd, DD MMMM YYYY');

        $journalNow = TeachingJournal::where('id_class_hour_details', $detail->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $journalBefore = TeachingJournal::where('id_class_hour_details', $detail->id)
            ->whereDate('date', '<', now()->toDateString())
            ->orderBy('date', 'desc')
            ->first();


        return view($this->path . 'show', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'detail' => $detail,
            'students' => $students,
            'existingAttendance' => $existingAttendance,
            'formattedDate' => $formattedDate,
            'journalNow' => $journalNow,
            'journalBefore' => $journalBefore
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_class_hour_details' => 'required|exists:class_hour_details,id',
            'status' => 'required|array',
            'status.*' => 'in:hadir,izin,sakit,alfa',
        ]);

        $detailId = $request->id_class_hour_details;
        $date = now()->format('Y-m-d');
        $statuses = $request->status;

        $attendances = collect($statuses)->map(function ($status, $studentId) use ($detailId, $date) {
            return [
                'id_student' => $studentId,
                'id_class_hour_details' => $detailId,
                'date' => $date,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->values()->all();

        AttendanceStudents::upsert(
            $attendances,
            ['id_student', 'id_class_hour_details', 'date'],
            ['status', 'updated_at']
        );

        return redirect()->back()->with('success', 'Absensi berhasil disimpan!');
    }

    public function storeJournal(Request $request)
    {
        $request->validate([
            'id_class_hour_details' => 'required|exists:class_hour_details,id',
            'date' => 'required|date',
            'chapter' => 'nullable|string|max:255',
            'subject_matter' => 'nullable|string',
        ]);

        TeachingJournal::updateOrCreate(
            [
                'id_class_hour_details' => $request->id_class_hour_details,
                'date' => $request->date,
            ],
            [
                'chapter' => $request->chapter,
                'subject_matter' => $request->subject_matter,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Jurnal pembelajaran berhasil disimpan!',
            'redirect_url' => route('employee.teaching-schedule.index')
        ]);
    }
}
