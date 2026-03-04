<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    private $title = 'label.activity';
    private $icon = 'ti ti-history-toggle';
    private $path = 'backend.service.activity.';

    public function index(Request $request)
    {
        $date = (empty($request->date)) ? date('d-m-Y') : $request->date;
        $students = [];
        $student = Student::select('id')->whereIdParent(Auth::user()->parent->id)->get();

        foreach ($student as $s)
            array_push($students, $s->id);

        return view($this->path . 'index', [
            'title' => __($this->title),
            'icon' => $this->icon,
            'date' => $date,
            'students' => json_encode($students)
        ]);
    }

    public function get(Request $request)
    {
        $students = $request->students;
        $page = $request->page;
        $limit = 5;
        $offset = ($page - 1) * $limit;

        $activity = Activity::select('icon', 'title', 'message', 'created_at')
            ->whereDate('created_at', date('Y-m-d', strtotime($request->date)))
            ->whereIn('id_student', $students)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $list = view($this->path . 'get', [
            'activity' => $activity,
        ])->render();

        $response = [
            'status' => true,
            'message' => 'Ok',
            'data' => [
                'count' => $activity->count(),
                'list' => $list
            ]
        ];

        return response()->json($response);
    }
}
