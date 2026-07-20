<?php

namespace App\Providers;

use App\Models\Election;
use App\Models\ProjectProposal;
use App\Models\Vote;
use App\Policies\ElectionPolicy;
use App\Policies\ProjectProposalPolicy;
use App\Policies\VotePolicy;
use App\Support\NostrAuth;
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

        // Profil-Seed (GET /nostr/profiles): jede Anfrage kann bis zu 100 pubkeys
        // tragen und bei Cache-Miss eine WS-Verbindung zum Indexer auslösen. 30/min
        // deckt den realen Bedarf klar ab — ein Raumwechsel kostet meist einen
        // Aufruf, ein sehr großer Raum wenige — begrenzt aber die Relay-Arbeit, die
        // ein einzelnes Konto anstoßen kann. Schlüssel ist der angemeldete pubkey
        // (der Endpunkt ist ohnehin nur angemeldet erreichbar), damit ein Nutzer
        // hinter geteilter IP nicht alle anderen mit ausbremst.
        RateLimiter::for('nostr-profiles', function (Request $request) {
            return Limit::perMinute(30)->by(NostrAuth::pubkey() ?? $request->ip());
        });

        RateLimiter::for('nostr-login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
