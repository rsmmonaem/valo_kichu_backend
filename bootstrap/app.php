<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'optional.auth' => \App\Http\Middleware\OptionalAuth::class,
            'parse.multipart.put' => \App\Http\Middleware\ParseMultipartPut::class,
            'hmac.auth' => \App\Http\Middleware\HmacAuthMiddleware::class,
            'ip.security' => \App\Http\Middleware\IpSecurityMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
