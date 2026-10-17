<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allowlist route untuk akun orang tua (orang tua biasa, atau pegawai nonaktif yang juga orang tua).
 * Pegawai-orang-tua (dua peran) tidak dibatasi: mereka punya route pegawai dan route orang tua.
 *
 * Mode di config/parent_access.php (PARENT_ROUTE_MODE):
 *   log     -> route di luar allowlist tetap boleh, dicatat ke log (route, user, waktu)
 *   enforce -> diarahkan ke dashboard
 */
class ParentRouteAllowlist
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->menu_role === UserRole::OrangTua && ! $this->allowed($request)) {
            if (config('parent_access.mode') === 'enforce') {
                return $request->expectsJson()
                    ? response()->json(['status' => false, 'message' => __('string.page_not_available')], 403)
                    : redirect()->route('dashboard.index');
            }

            Log::warning('parent-route-outside-allowlist', [
                'route' => $request->route()?->getName() ?? $request->path(),
                'method' => $request->method(),
                'user_id' => $user->id,
                'at' => now()->toDateTimeString(),
            ]);
        }

        return $next($request);
    }

    private function allowed(Request $request): bool
    {
        $name = $request->route()?->getName();

        return $name !== null && Str::is(config('parent_access.routes', []), $name);
    }
}
