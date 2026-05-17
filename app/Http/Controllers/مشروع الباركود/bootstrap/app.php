<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'not_blocked' => \App\Http\Middleware\CheckIfNotBlocked::class,
            'user' => \App\Http\Middleware\CheckIfUser::class,
            'admin' => \App\Http\Middleware\CheckIfAdmin::class,
            'super_admin' => \App\Http\Middleware\CheckIfSuperAdmin::class,
            'admin_or_super_admin' => \App\Http\Middleware\CheckIfSuperAdminOrAdmin::class,
            'checkOfAdminVersion' => \App\Http\Middleware\CheckOfAdminVersion::class,
            'checkOfUserVersion' => \App\Http\Middleware\CheckOfUserVersion::class

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {});
    })->create();
