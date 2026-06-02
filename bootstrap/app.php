<?php

use App\Services\SecurityMonitor;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            ThrottleRequests::class.':api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Record Livewire tampering exceptions, then return false to stop them
        // reaching Sentry/Nightwatch/log. Must run before Integration::handles()
        // (callbacks fire in order; false short-circuits the rest). dontReport()
        // is unusable here — it short-circuits before the recording would run.
        $exceptions->report(function (Throwable $e): bool {
            $monitor = app(SecurityMonitor::class);

            if ($monitor->shouldRecord($e)) {
                $monitor->recordFromException($e);

                return false;
            }

            return true;
        });

        Integration::handles($exceptions);
    })->create();
