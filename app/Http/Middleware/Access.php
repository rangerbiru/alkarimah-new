<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

/**
 * Seperti Role, tapi akun pegawai yang memegang modul TU terkait juga lolos
 * (User::hasAccess). Role yang tidak punya modul (admin, orang-tua, ...) hanya
 * lolos bila role-nya sendiri ada di daftar.
 */
class Access
{
    public function handle(Request $request, Closure $next, ...$accesses): Response
    {
        if (! Auth::check()) {
            return redirect()->route('base');
        }

        $user = Auth::user();

        foreach ($accesses as $access) {
            if ($user->hasAccess($access)) {
                return $next($request);
            }
        }

        return Redirect::route('base');
    }
}
