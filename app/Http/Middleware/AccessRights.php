<?php

namespace App\Http\Middleware;

use App\Models\ModuleRights;
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
        $module_rights = ModuleRights::select('id_module')->whereIdUser(Auth::id())->pluck('id_module', 'id_module')->toArray();

        if (in_array($module, $module_rights))
            return $next($request);

        return Redirect::route('base');
    }
}
