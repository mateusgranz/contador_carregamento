<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Atrás do proxy do Coolify: sem isso o Laravel gera URLs em http
        // e os cookies "secure" não funcionam sob HTTPS.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'gestor'     => \App\Http\Middleware\EnsureGestor::class,
            'carregador' => \App\Http\Middleware\EnsureCarregador::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
