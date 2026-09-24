<?php

use App\Http\Middleware\EnsureApproved;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        error_log('[VERCEL-BOOT] Registering middleware');
        $middleware->web(prepend: [
            SecurityHeaders::class,
        ]);
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
        $middleware->alias([
            'role' => EnsureRole::class,
            'approved' => EnsureApproved::class,
            'throttle' => ThrottleRequests::class,
            'pembina' => EnsureRole::class.':Pembina,Admin',
        ]);
        $middleware->redirectTo(guests: fn () => route('login'));
        $middleware->trustProxies('*');
        error_log('[VERCEL-BOOT] Middleware registered');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        error_log('[VERCEL-BOOT] Configuring exception handler');
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        error_log('[VERCEL-BOOT] Exception handler configured');
    })->create();
