<?php

use App\Models\EinundzwanzigPleb;
use App\Support\NostrAuth;
use Livewire\Livewire;

it('shows the locked state with all four services for guests', function () {
    Livewire::test('association.benefits')
        ->assertSet('currentYearIsPaid', false)
        ->assertSee('Dienste gesperrt')
        ->assertSee('Blossom-Medienserver')
        ->assertSee('5 GB Speicher')
        ->assertSee('max. 1 GB pro Datei')
        ->assertDontSee('https://blossom.einundzwanzig.space');
});

it('unlocks the blossom server for active paid members', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();
    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.benefits')
        ->assertSet('currentYearIsPaid', true)
        ->assertSee('Mitgliedschaft aktiv')
        ->assertSee('Blossom Medienserver')
        ->assertSee('https://blossom.einundzwanzig.space');
});

it('copies the blossom url for active members', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();
    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.benefits')
        ->call('copyBlossomUrl')
        ->assertHasNoErrors();
});

it('unlocks the nostr community group for active paid members', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();
    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.benefits')
        ->assertSee('Nostr Community-Gruppe')
        ->assertSee('app.flotilla.social/spaces/group.einundzwanzig.space')
        ->assertSee('wss://group.einundzwanzig.space');
});

it('copies the community relay url for active members', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();
    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.benefits')
        ->call('copyGroupRelayUrl')
        ->assertHasNoErrors();
});
