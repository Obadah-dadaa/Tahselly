<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\LiveOnly;
use App\Http\Middleware\RequirePlanFeature;
use App\Http\Middleware\RequireStaff;
use App\Http\Middleware\SetPreferences;
use App\Http\Middleware\TrackVisit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => EnsureRole::class, 'feature' => RequirePlanFeature::class, 'track' => TrackVisit::class, 'staff' => RequireStaff::class, 'live' => LiveOnly::class]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/home');
        // Stripe calls this endpoint server-to-server; it is authenticated by its signature instead
        $middleware->validateCsrfTokens(except: ['stripe/webhook']);
        $middleware->web(append: [SetPreferences::class]);
        // Shared hosting / load balancers terminate HTTPS in front of the app
        $middleware->trustProxies(at: '*');
        // Only answer for our own domain (plus local hosts in development)
        $middleware->trustHosts(at: fn () => array_filter([parse_url((string) config('app.url'), PHP_URL_HOST), app()->isProduction() ? null : 'localhost']));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
