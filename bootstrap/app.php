<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'chat/*',
        ]);

        // Register authentication middleware aliases
        $middleware->alias([
            'mcp.auth' => \App\Http\Middleware\ValidateMcpToken::class,
            'keycloak.auth' => \App\Http\Middleware\AuthenticateWithKeycloak::class,
            'authorized.user' => \App\Http\Middleware\CheckAuthorizedUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
