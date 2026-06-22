<?php

use App\Models\EinundzwanzigPleb;
use App\Support\NostrAuth;
use Livewire\Livewire;

function activePaidPleb(): EinundzwanzigPleb
{
    $pleb = EinundzwanzigPleb::factory()->active()->create();

    $pleb->paymentEvents()->create([
        'year' => date('Y'),
        'amount' => 21000,
        'event_id' => 'event-benefits',
        'paid' => true,
    ]);

    return $pleb;
}

it('shows the locked state with all four services for guests', function () {
    Livewire::test('association.benefits')
        ->assertSet('currentYearIsPaid', false)
        ->assertSee('Dienste gesperrt')
        ->assertSee('Blossom-Medienserver')
        ->assertDontSee('https://blossom.einundzwanzig.space');
});

it('unlocks the blossom server for active paid members', function () {
    $pleb = activePaidPleb();
    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.benefits')
        ->assertSet('currentYearIsPaid', true)
        ->assertSee('Mitgliedschaft aktiv')
        ->assertSee('Blossom Medienserver')
        ->assertSee('https://blossom.einundzwanzig.space');
});

it('copies the blossom url for active members', function () {
    $pleb = activePaidPleb();
    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.benefits')
        ->call('copyBlossomUrl')
        ->assertHasNoErrors();
});
