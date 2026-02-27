<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->remove(\Illuminate\Http\Middleware\TrustProxies::class);
        $middleware->prepend(\App\Http\Middleware\TrustProxies::class);
        // Exclude api (internal) proxy routes from CSRF when request is from our own HTTP client looping back
        // Exclude logout so it works when session/cookie would otherwise cause 419 Page Expired
        $middleware->validateCsrfTokens(except: [
            'api/login',
            'api/register',
            'api/v1/*',
            'logout',
        ]);
        $middleware->alias([
            'auth.api' => \App\Http\Middleware\EnsureAuthenticated::class,
            'guest.api' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'user.in.session' => \App\Http\Middleware\EnsureUserInSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, \Throwable $e) {
            return $request->expectsJson() || $request->is('recipes/product-create');
        });
    })->create();
