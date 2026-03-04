<?php

namespace App\Http\Middleware;

use App\Models\AllowedSubmissionEmployee;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAllowedSubmissionEmployee
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->employee) {
            return abort(403);
        }

        $employeeId = Auth::user()->employee->id;

        $allowedRecord = AllowedSubmissionEmployee::where('employee_id', $employeeId)->first();

        if (!$allowedRecord) {
            return abort(403, 'Anda tidak diizinkan mengakses fitur ini.');
        }

        $privilegedPositions = ['atk', 'it', 'sarpras'];

        $action = $request->route()->getActionMethod();

        if (in_array($action, ['create', 'store', 'edit', 'update', 'destroy'])) {
            if (!in_array($allowedRecord->position, $privilegedPositions)) {
                return abort(403, 'Hanya ATk, IT, dan Sarpras yang dapat melakukan perubahan data.');
            }
        }

        return $next($request);
    }
}
