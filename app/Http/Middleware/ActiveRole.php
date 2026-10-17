<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Konteks peran untuk akun yang juga orang tua.
 *
 * - Pegawai-orang-tua: menu digabung, tetapi request ke route khusus orang tua dijalankan sebagai
 *   orang tua (role di memori = orang-tua, tidak pernah tersimpan ke DB). Request ke route staf
 *   tetap sebagai staf. Dengan begitu controller/view yang bercabang per role tidak perlu diubah.
 * - Pegawai nonaktif yang juga orang tua: seluruh request sebagai orang tua.
 * - Akun yang bertindak sebagai orang tua (termasuk orang tua biasa) dicek terhadap allowlist
 *   config/parent_access.php (mode log / enforce).
 *
 * Dipasang di grup route auth, sebelum middleware role:/access: pada tiap route.
 */
class ActiveRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        // Instance user yang sama bisa dipakai ulang antar request: jangan ada override yang bocor
        $user->clearActAs();

        if ($user->role !== UserRole::OrangTua && ($user->isParentOnlyAccount() || $this->needsParentContext($user, $request->route()))) {
            $user->actAs('parent');
        }

        if ($user->role === UserRole::OrangTua && ! $this->allowed($request)) {
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

    /**
     * Route ini dibuka untuk orang-tua dan staf yang tidak dipenuhi user ini sebagai staf, sedangkan
     * user adalah staf yang juga orang tua (punya santri).
     */
    private function needsParentContext($user, ?Route $route): bool
    {
        if (! $route) {
            return false;
        }

        $found = false;

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            [$name, $params] = explode(':', $middleware, 2) + [1 => ''];

            if (! in_array($name, ['role', 'access'], true)) {
                continue;
            }

            $list = explode(',', $params);

            if (! in_array(UserRole::OrangTua->value, $list, true)) {
                continue;
            }

            foreach (array_diff($list, [UserRole::OrangTua->value]) as $staff) {
                if ($name === 'role' ? $user->hasRole($staff) : $user->hasAccess($staff)) {
                    return false;
                }
            }

            $found = true;
        }

        return $found && $user->hasMultipleRoles();
    }

    private function allowed(Request $request): bool
    {
        $name = $request->route()?->getName();

        return $name !== null && Str::is(config('parent_access.routes', []), $name);
    }
}
