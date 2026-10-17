<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun alias (users.linked_user_id terisi) tidak pernah menjadi user aktif di sesi. Sesi lama atau
 * cookie "ingat saya" milik akun alias dialihkan ke akun pegawai tujuannya; bila tujuannya tidak valid
 * (hilang, terhapus, atau alias juga) sesi diakhiri. Login baru sudah menyelesaikan alias di
 * AuthController, middleware ini hanya menutup sesi yang terbentuk sebelum penggabungan.
 */
class ResolveAlias
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->linked_user_id) {
            $target = User::find($user->linked_user_id);

            if ($target && ! $target->linked_user_id) {
                Auth::login($target);
            } else {
                Log::warning('session-alias-target-invalid', ['alias_user_id' => $user->id, 'target' => $user->linked_user_id]);

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return $request->expectsJson()
                    ? response()->json(['status' => false, 'message' => __('string.otp_session_invalid')], 401)
                    : Redirect::route('base');
            }
        }

        return $next($request);
    }
}
