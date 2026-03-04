<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\AttendanceGroup;
use App\Models\AttendanceGroupMembers;
use App\Models\AttendanceGroupDays;
use App\Models\AttendanceLocation;
use App\Models\AttendanceShifts;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceGroupController extends Controller
{
    private $title = 'label.attendance_group';
    private $icon = 'bx bx-building';
    private $path = 'backend.hr.attendance.group.';

    public function index()
    {
        $count = AttendanceGroup::count();

        $groups = AttendanceGroup::with('days', 'shift')->get();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'count' => $count,
            'groups' => $groups
        ]);
    }

    public function create()
    {

        $positions = Position::select('id', 'name')->pluck('name', 'id');
        return view($this->path . 'create', [
            'title' => "Tambah " . __($this->title),
            'icon' => $this->icon,
            'positions' => $positions
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_name'      => 'required',
            'position'        => 'required',
            'day'             => 'required|array',
            'shift_work'      => 'required',

            // Kondisional untuk NON SHIFT (N)
            'check_in_time'   => $request->shift_work == 'N' ? 'required' : 'nullable',
            'check_out_time'  => $request->shift_work == 'N' ? 'required' : 'nullable',
            'tolerance_in'    => $request->shift_work == 'N' ? 'required|integer|min:0' : 'nullable',
            'tolerance_out'   => $request->shift_work == 'N' ? 'required|integer|min:0' : 'nullable',

            'description'     => 'nullable',
        ]);


        if (AttendanceGroup::where('group_name', strtolower($request->group_name))->exists()) {
            return back()->with('error', 'Data sudah ada');
        }

        $group = AttendanceGroup::create([
            'group_name' => $validated['group_name'],
            'position' => $validated['position'],
            'shift_work' => $validated['shift_work'],
            'description' => $validated['description'] ?? null,
        ]);

        foreach ($validated['day'] as $day) {
            if ($request->shift_work === 'N') {
                AttendanceGroupDays::create([
                    'attendance_group_id' => $group->id,
                    'day_name' => $day,
                    'check_in_time' => \Carbon\Carbon::parse($validated['check_in_time'])->format('H:i:s'),
                    'check_out_time' => \Carbon\Carbon::parse($validated['check_out_time'])->format('H:i:s'),
                    'tolerance_in' => $validated['tolerance_in'],
                    'tolerance_out' => $validated['tolerance_out'],
                ]);
            } else {
                $shiftValidated = $request->validate([
                    'shift1_check_in_time'   => 'nullable',
                    'shift1_check_out_time'  => 'nullable',

                    'shift2_check_in_time'   => 'nullable',
                    'shift2_check_out_time'  => 'nullable',
                    'shift3_check_in_time'   => 'nullable',
                    'shift3_check_out_time'  => 'nullable',
                ]);

                $shift = AttendanceShifts::create([
                    'attendance_group_id' => $group->id,
                    'day_name' => $day,

                    'shift1_check_in_time'  => Carbon::parse($shiftValidated['shift1_check_in_time'])->format('H:i:s'),
                    'shift1_check_out_time' => Carbon::parse($shiftValidated['shift1_check_out_time'])->format('H:i:s'),

                    'shift2_check_in_time'  => $shiftValidated['shift2_check_in_time']
                        ? Carbon::parse($shiftValidated['shift2_check_in_time'])->format('H:i:s')
                        : null,

                    'shift2_check_out_time' => $shiftValidated['shift2_check_out_time']
                        ? Carbon::parse($shiftValidated['shift2_check_out_time'])->format('H:i:s')
                        : null,

                    'shift3_check_in_time'  => $shiftValidated['shift3_check_in_time']
                        ? Carbon::parse($shiftValidated['shift3_check_in_time'])->format('H:i:s')
                        : null,

                    'shift3_check_out_time' => $shiftValidated['shift3_check_out_time']
                        ? Carbon::parse($shiftValidated['shift3_check_out_time'])->format('H:i:s')
                        : null,

                ]);

                if (!$request->shift1_check_in_time && !$request->shift2_check_in_time && !$request->shift3_check_in_time) {
                    return back()->with('error', 'Minimal satu shift harus diisi.');
                }

                AttendanceGroupDays::create([
                    'attendance_group_id' => $group->id,
                    'day_name' => $day,
                    'shift_id' => $shift->id,
                    'tolerance_in' => $validated['tolerance_in'],
                    'tolerance_out' => $validated['tolerance_out'],
                ]);
            }
        }

        return redirect()->route('hr.attendance.group.index')->with('success', 'Data berhasil disimpan');
    }


    public function updateTime(Request $request, $id)
    {
        $group = AttendanceGroup::findOrFail($id);

        foreach ($request->days as $dayName => $time) {
            if (!empty($time['check_in_time']) || !empty($time['check_out_time'])) {
                $day = AttendanceGroupDays::where('attendance_group_id', $group->id)
                    ->where('day_name', $dayName)
                    ->first();

                if ($day) {
                    $day->update([
                        'check_in_time' => $time['check_in_time'],
                        'tolerance_in' => $time['tolerance_in'],
                        'check_out_time' => $time['check_out_time'],
                        'tolerance_out' => $time['tolerance_out'],
                    ]);
                } else {
                    AttendanceGroupDays::create([
                        'attendance_group_id' => $group->id,
                        'day_name' => $dayName,
                        'check_in_time' => $time['check_in_time'],
                        'tolerance_in' => $time['tolerance_in'],
                        'check_out_time' => $time['check_out_time'],
                        'tolerance_out' => $time['tolerance_out'],
                    ]);
                }
            }
        }

        return back()->with('success', 'Data berhasil diperbarui');
    }

    public function updateTimeShift(Request $request, $id)
    {
        $group = AttendanceGroup::findOrFail($id);

        foreach ($request->shifts as $dayName => $shiftData) {

            $hasShiftData =
                !empty($shiftData['shift1_check_in_time']) ||
                !empty($shiftData['shift2_check_in_time']) ||
                !empty($shiftData['shift3_check_in_time']) ||
                !empty($shiftData['shift1_check_out_time']) ||
                !empty($shiftData['shift2_check_out_time']) ||
                !empty($shiftData['shift3_check_out_time']) ||
                !empty($shiftData['tolerance_in']) ||
                !empty($shiftData['tolerance_out']);

            if (!$hasShiftData) {
                continue;
            }

            $shift = AttendanceShifts::updateOrCreate(
                [
                    'attendance_group_id' => $group->id,
                    'day_name' => $dayName,
                ],
                [
                    'shift1_check_in_time' => $shiftData['shift1_check_in_time'] ?? null,
                    'shift1_check_out_time' => $shiftData['shift1_check_out_time'] ?? null,
                    'shift2_check_in_time' => $shiftData['shift2_check_in_time'] ?? null,
                    'shift2_check_out_time' => $shiftData['shift2_check_out_time'] ?? null,
                    'shift3_check_in_time' => $shiftData['shift3_check_in_time'] ?? null,
                    'shift3_check_out_time' => $shiftData['shift3_check_out_time'] ?? null,
                ]
            );


            AttendanceGroupDays::updateOrCreate(
                [
                    'attendance_group_id' => $group->id,
                    'day_name' => $dayName,
                ],
                [
                    'shift_id' => $shift->id,
                    'tolerance_in' => $shiftData['tolerance_in'] ?? null,
                    'tolerance_out' => $shiftData['tolerance_out'] ?? null,
                ]
            );
        }

        return back()->with('success', 'Data shift berhasil diperbarui');
    }

    public function updateShift(Request $request, $id)
    {
        $request->validate([
            'shift_work' => 'required|in:Y,N'
        ]);

        $group = AttendanceGroup::findOrFail($id);
        $group->update(['shift_work' => $request->shift_work]);

        return response()->json([
            'success' => true,
            'message' => 'Status shift berhasil diperbarui.'
        ]);
    }



    public function edit($id)
    {
        $data = AttendanceGroup::with('days')->find($id);
        $dayTimes = $data->days->mapWithKeys(function ($item) {
            return [
                $item->day_name => [
                    'check_in_time' => $item->check_in_time,
                    'check_out_time' => $item->check_out_time,
                ]
            ];
        });

        return view($this->path . 'edit', [
            'title' => "Edit " . __($this->title),
            'icon' => $this->icon,
            'data' => $data,
            'dayTimes' => $dayTimes
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'group_name' => 'required',
            'check_in_time' => 'required',
            'check_out_time' => 'required',
            'description' => 'nullable',
        ]);

        if (AttendanceGroup::where('group_name', strtolower($request->group_name))->where('id', '!=', $id)->exists()) {
            return back()->with('error', 'Data sudah ada');
        }

        AttendanceGroup::where('id', $id)->update([
            'group_name' => strtolower($request->group_name),
            'check_in_time'  => \Carbon\Carbon::parse($request->check_in_time)->format('H:i:s'),
            'check_out_time' => \Carbon\Carbon::parse($request->check_out_time)->format('H:i:s'),
            'description' => $request->description,
        ]);

        return redirect()->route('hr.attendance.group.index')->with('success', 'Data berhasil diubah');
    }


    public function destroy($id)
    {
        AttendanceGroupMembers::where('attendance_group_id', $id)->delete();
        $attendanceGroup = AttendanceGroup::destroy($id);

        if ($attendanceGroup) {
            AttendanceGroupDays::where('attendance_group_id', $id)->delete();
            AttendanceLocation::where('attendance_group_id', $id)->delete();
            AttendanceShifts::where('attendance_group_id', $id)->delete();
        }
        return back()->with('success', 'Data berhasil dihapus');
    }

    public function datatable(Request $request)
    {
        $search = $request->input('search')['value'] ?? null;
        $limit  = $request->input('length') ?? 10;
        $start  = $request->input('start') ?? 0;

        $query = AttendanceGroup::with('position', 'shift');

        $recordsTotal = $query->count();

        if (!empty($search)) {
            $query->where('group_name', 'like', '%' . $search . '%');
        }

        $recordsFiltered = $query->count();

        $data = $query->orderBy('created_at', 'desc')
            ->offset($start)
            ->limit($limit)
            ->get();

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data
        ]);
    }
}
