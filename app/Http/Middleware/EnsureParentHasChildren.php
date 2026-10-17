<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

// Parent pages (payment, top up, savings) need at least one child; send the parent to Data Anak first
class EnsureParentHasChildren
{
    public function handle(Request $request, Closure $next): Response
    {
        $parent = Auth::user()->parent;

        if (! $parent || ! Student::whereIdParent($parent->id)->exists()) {
            return Redirect::route('academic.student.children')->with('info', __('message.child_required'));
        }

        return $next($request);
    }
}
