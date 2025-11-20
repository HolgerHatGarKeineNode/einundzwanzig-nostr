<?php

namespace App\Providers;

use App\Auth\NostrSessionGuard;
use App\Auth\NostrUserProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class NostrAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Auth::provider('nostr', function (Application $app, array $config) {
            return new NostrUserProvider();
        });

        Auth::extend('nostr-session', function (Application $app, string $name, array $config) {
            $provider = Auth::createUserProvider($config['provider']);

            return new NostrSessionGuard(
                $name,
                $provider,
                $app['session.store'],
                $app['request']
            );
        });
    }
}
