<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class AccessRights
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $module): Response
    {
        // Admin lolos semua modul. Route yang tidak boleh dibuka admin dibatasi
        // oleh middleware role: yang berjalan lebih dulu (mis. academic.absence.create).
        if (Auth::user()?->role === UserRole::Admin)
            return $next($request);

        // Lewat User::moduleIds() agar modul tidak berlaku saat bertindak sebagai orang tua
        $module_rights = Auth::user()?->moduleIds() ?? [];

        if (in_array($module, $module_rights))
            return $next($request);

        return Redirect::route('base');
    }
}
