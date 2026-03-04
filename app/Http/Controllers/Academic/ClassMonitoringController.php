<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AttendanceStudents;
use App\Models\ClassHourDetail;
use App\Models\ClassHours;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ClassMonitoringController extends Controller
{
    private string $title = 'label.class_monitoring';
    private string $icon = 'bx bx-desktop';
    private string $path = 'backend.academic.monitoring.';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::today();

        $searchDate = $date->format('Y-m-d');
        $day = strtolower($date->locale('id')->dayName);

        // Parsing filter kelas (jika ada)
        $levelEducation = null;
        $levelClass = null;

        if ($request->filled('class')) {
            $parts = explode('|', $request->class);
            if (count($parts) === 2) {
                [$levelEducation, $levelClass] = $parts;
            }
        }

        // Ambil data jam pelajaran dengan relasi yang diperlukan
        $classHours = ClassHours::with([
            'details' => function ($q) use ($day, $searchDate) {
                $q->where('day', $day)
                    ->with(['teacher', 'subject'])
                    // Hitung jumlah absensi HARI INI untuk setiap detail
                    ->withCount(['attendance as has_attendance_today' => function ($sub) use ($searchDate) {
                        $sub->where('date', $searchDate);
                    }]);
            },
            'class'
        ])
            ->whereHas('details', function ($q) use ($day) {
                $q->where('day', $day);
            })
            ->whereHas('class', function ($q) use ($levelEducation, $levelClass) {
                $q->when($levelEducation, fn($q) => $q->where('level_education', $levelEducation))
                    ->when($levelClass, fn($q) => $q->where('level_class', $levelClass));
            })
            ->get();

        // Inisialisasi variabel statistik
        $totalHadir = 0;
        $totalBelumAbsen = 0;
        $processedTeachers = [];
        $activeJam = null;
        $kelasBelumAbsen = [];
        $currentTime = now();

        // Proses setiap jam pelajaran
        foreach ($classHours as $classHour) {
            // Urutkan detail berdasarkan jp_number (angka di awal)
            $details = $classHour->details->sortBy(function ($detail) {
                return is_numeric($detail->jp_number) ? (int) $detail->jp_number : 999;
            });

            // Grouping JP berurutan dengan guru & mapel sama
            $groupedDetails = [];
            $currentGroup = [];
            $previousDetail = null;

            foreach ($details as $detail) {
                $isConsecutive = false;

                if (is_numeric($detail->jp_number) && $previousDetail && is_numeric($previousDetail->jp_number)) {
                    if (
                        (int) $detail->jp_number === (int) $previousDetail->jp_number + 1 &&
                        $detail->id_subject === $previousDetail->id_subject &&
                        $detail->id_employee === $previousDetail->id_employee
                    ) {
                        $isConsecutive = true;
                    }
                }

                if ($isConsecutive) {
                    $currentGroup[] = $detail;
                } else {
                    if (!empty($currentGroup)) {
                        $groupedDetails[] = $currentGroup;
                    }
                    $currentGroup = [$detail];
                }
                $previousDetail = $detail;
            }

            if (!empty($currentGroup)) {
                $groupedDetails[] = $currentGroup;
            }

            // Proses tiap group
            foreach ($groupedDetails as $group) {
                // Cek apakah ADA SATU SAJA yang sudah absen hari ini
                $groupHasAttendance = collect($group)->contains(fn($d) => $d->has_attendance_today > 0);

                foreach ($group as $detail) {
                    // Tandai status absensi
                    $detail->has_attendance = $groupHasAttendance;

                    // Hitung statistik guru (hindari double count)
                    if ($detail->id_employee) {
                        $teacherKey = $detail->id_employee . '_' . $detail->id_subject;
                        if (!isset($processedTeachers[$teacherKey])) {
                            if ($groupHasAttendance) {
                                $totalHadir++;
                            } else {
                                $totalBelumAbsen++;
                            }
                            $processedTeachers[$teacherKey] = true;
                        }
                    }

                    // Cek jam aktif berdasarkan waktu sekarang
                    if (is_numeric($detail->jp_number)) {
                        $startTime = Carbon::parse($detail->start_time);
                        $endTime = Carbon::parse($detail->end_time);

                        $currentDayStartTime = Carbon::today()->setTimeFromTimeString($startTime->format('H:i:s'));
                        $currentDayEndTime = Carbon::today()->setTimeFromTimeString($endTime->format('H:i:s'));

                        if ($currentTime->between($currentDayStartTime, $currentDayEndTime)) {
                            $activeJam = (int) $detail->jp_number;
                        }
                    }
                }
            }
        }

        // Cari kelas yang belum absen di jam aktif
        if ($activeJam !== null) {
            foreach ($classHours as $classHour) {
                $detail = $classHour->details->firstWhere('jp_number', (string) $activeJam);
                if ($detail && $detail->id_employee && !$detail->has_attendance) {
                    $kelasBelumAbsen[] = $classHour->name;
                }
            }
        }

        // Daftar kelas untuk filter
        $classList = Classroom::query()
            ->whereIn('level_education', ['smp', 'sma'])
            ->whereIn('level_class', [1, 2, 3])
            ->orderBy('level_education')
            ->orderBy('level_class')
            ->get()
            ->mapWithKeys(function ($item) {
                $education = $item->level_education->value;
                $level     = $item->level_class;

                $key   = $education . '|' . $level;
                $label = strtoupper($level . ' ' . $education);

                return [$key => $label];
            })
            ->unique()
            ->toArray();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'classHours' => $classHours,
            'date' => $date,
            'classList' => $classList,
            'totalHadir' => $totalHadir,
            'totalBelumAbsen' => $totalBelumAbsen,
            'activeJam' => $activeJam,
            'kelasBelumAbsen' => $kelasBelumAbsen,
        ]);
    }
}