<?php

use App\Http\Middleware\Access;
use App\Http\Middleware\AccessRights;
use App\Http\Middleware\EnsureParentHasChildren;
use App\Http\Middleware\InitializeBackend;
use App\Http\Middleware\ParentRouteAllowlist;
use App\Http\Middleware\Role;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo('/');

        $middleware->alias([
            'initialize.backend' => InitializeBackend::class,
            'parent.allowlist' => ParentRouteAllowlist::class,
            'parent.has-children' => EnsureParentHasChildren::class,
            'role' => Role::class,
            'access' => Access::class,
            'accessRights' => AccessRights::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'moota/notification/*'
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
