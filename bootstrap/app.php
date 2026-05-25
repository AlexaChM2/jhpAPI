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
    ->withMiddleware(function (Middleware $middleware) {
        // Agrega CORS como middleware global
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
        
        // Solo mantener el middleware que sí existe
        $middleware->alias([
            'cliente.propietario' => \App\Http\Middleware\ClientePropietario::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();