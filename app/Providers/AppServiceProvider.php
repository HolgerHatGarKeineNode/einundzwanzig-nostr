<?php

namespace App\Providers;

use App\Models\Election;
use App\Models\ProjectProposal;
use App\Models\Vote;
use App\Policies\ElectionPolicy;
use App\Policies\ProjectProposalPolicy;
use App\Policies\VotePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ProjectProposal::class, ProjectProposalPolicy::class);
        Gate::policy(Vote::class, VotePolicy::class);
        Gate::policy(Election::class, ElectionPolicy::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('voting', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('nostr-login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
