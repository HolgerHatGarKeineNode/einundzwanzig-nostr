<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Bindet das Package einundzwanzig/group als reine Bausteinsammlung ein.
 *
 * Das Package ist als eigenständige Chat-Anwendung gebaut: sein eigener
 * GroupServiceProvider registriert Routen (/spaces, /rooms/{h}, /settings, …),
 * einen zweiten Login-Pfad über NIP-98 und einen Scheduler. Nichts davon wollen
 * wir hier — der Verein hat eine eigene Navigation und einen eigenen
 * Nostr-Login (kind 22242, siehe App\Support\NostrAuth). Der Package-Provider
 * ist deshalb in composer.json unter extra.laravel.dont-discover abgeschaltet.
 *
 * Übrig bleibt, was wir tatsächlich brauchen: die Views und Blade-Komponenten
 * der Chat-Insel. Wächst der Bedarf, kommt hier eine Zeile dazu — bewusst und
 * einzeln, statt den ganzen Provider zu erben.
 */
class GroupPackageServiceProvider extends ServiceProvider
{
    /**
     * Pfad zum installierten Package.
     */
    protected function packagePath(string $sub = ''): string
    {
        return base_path('vendor/einundzwanzig/group'.$sub);
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->packagePath('/config/group.php'), 'group');
    }

    public function boot(): void
    {
        $views = $this->packagePath('/resources/views');

        // group::partials.chat-row, group::partials.chat-composer, …
        $this->loadViewsFrom($views, 'group');

        // Die Package-Views nutzen __('Deutscher Text') als Key. Additiv — die
        // lang/*.json der App bleiben unberührt und haben Vorrang.
        $this->loadJsonTranslationsFrom($this->packagePath('/lang'));

        // <x-group::nostr-avatar>, <x-group::profile-card>, …
        Blade::anonymousComponentPath($views.'/components', 'group');
    }
}
