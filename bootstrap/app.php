<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\Role::class,
            'idle' => \App\Http\Middleware\IdleLogout::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        ]);
        $middleware->append(\App\Http\Middleware\ObfuscateHtmlMiddleware::class);
        $middleware->append(\App\Http\Middleware\IdleLogout::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
