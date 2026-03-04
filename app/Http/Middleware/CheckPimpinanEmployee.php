<?php

namespace App\Http\Middleware;

use App\Models\AllowedSubmissionEmployee;
use App\Models\AttendanceGroupMembers;
use App\Models\Departments;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPimpinanEmployee
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->employee) {
            return abort(403, 'Anda harus login sebagai pegawai.');
        }

        $employeeId = Auth::user()->employee->id;

        $inDepartment = Departments::where('employee_id', $employeeId)->exists();

        $isPimpinan = AttendanceGroupMembers::with('attendanceGroup')
            ->where('employee_id', $employeeId)
            ->whereHas('attendanceGroup', function ($query) {
                $query->where('position', 10);
            })->exists();

        if (!$inDepartment && !$isPimpinan) {
            return abort(403, 'Hanya anggota departemen atau Mudir yang dapat mengakses fitur ini.');
        }

        return $next($request);
    }
}