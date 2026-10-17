<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class Role
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! Auth::check())
            return redirect()->route('base');

        $user = Auth::user();

        // Inactive employee who is also a parent: parent routes only, never staff routes
        if ($user->acts_as_parent_only) {
            return in_array(UserRole::OrangTua->value, $roles) ? $next($request) : Redirect::route('base');
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role))
                return $next($request);
        }

        if ($this->allowedByDualRole($user, $roles)) {
            return $next($request);
        }

        return Redirect::route('base');
    }

    // An employee who is also a parent keeps one users.role, but may open both the
    // employee routes (role:pegawai) and the parent routes (role:orang-tua)
    private function allowedByDualRole($user, array $roles): bool
    {
        $needs_employee = in_array(UserRole::Pegawai->value, $roles);
        $needs_parent = in_array(UserRole::OrangTua->value, $roles);

        // Staff accounts (kasir, wali-kelas, ...) already pass their own role; the employee routes are
        // only extra for a dual-role account registered with the parent role
        return $user->has_dual_role && ($needs_parent || ($needs_employee && $user->role == UserRole::OrangTua));
    }
}
