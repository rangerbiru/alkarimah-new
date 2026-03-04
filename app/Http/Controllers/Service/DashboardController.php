<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    private $title = 'label.service';
    private $path = 'backend.service.dashboard.';

    public function index()
    {
        $students = [];
        $student = Student::select('id')->whereIdParent(Auth::user()->parent->id)->get();

        foreach ($student as $s)
            array_push($students, $s->id);

        $activity = Activity::select('icon', 'title', 'message', 'created_at')
            ->whereDate('created_at', date('Y-m-d'))
            ->whereIn('id_student', $students)
            ->orderBy('created_at', 'desc')
            ->get();

        return view($this->path . 'index', [
            'title' => __($this->title),
            'activity' => $activity
        ]);
    }
}
