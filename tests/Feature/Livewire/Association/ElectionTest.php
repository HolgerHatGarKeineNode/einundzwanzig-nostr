<?php

use App\Models\EinundzwanzigPleb;
use App\Models\Election;
use App\Support\NostrAuth;
use Livewire\Livewire;

it('loads elections on mount', function () {
    $election1 = Election::factory()->create(['year' => 2024]);
    $election2 = Election::factory()->create(['year' => 2025]);

    Livewire::test('association.election.index')
        ->assertSet('elections', function ($elections) {
            return count($elections) >= 2;
        });
});

it('denies access to unauthorized users in election index', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $election = Election::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.election.index', ['election' => $election])
        ->assertSet('isAllowed', false);
});

it('grants access to authorized users in election index', function () {
    $allowedPubkey = '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033';
    $pleb = EinundzwanzigPleb::factory()->create(['pubkey' => $allowedPubkey]);
    $election = Election::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.election.index', ['election' => $election])
        ->assertSet('isAllowed', true);
});

// Election Admin Tests
it('renders election admin component', function () {
    $election = Election::factory()->create();

    Livewire::test('association.election.admin', ['election' => $election])
        ->assertStatus(200);
});

it('denies access to unauthorized users in election admin', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $election = Election::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.election.admin', ['election' => $election])
        ->assertSet('isAllowed', false);
});

it('grants access to authorized users in election admin', function () {
    $allowedPubkey = '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033';
    $pleb = EinundzwanzigPleb::factory()->create(['pubkey' => $allowedPubkey]);
    $election = Election::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.election.admin', ['election' => $election])
        ->assertSet('isAllowed', true);
});

it('can save election candidates', function () {
    $allowedPubkey = '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033';
    $pleb = EinundzwanzigPleb::factory()->create(['pubkey' => $allowedPubkey]);
    $election = Election::factory()->create([
        'candidates' => json_encode([['type' => 'presidency', 'c' => []]]),
    ]);

    NostrAuth::login($pleb->pubkey);

    $newCandidates = json_encode([['type' => 'presidency', 'c' => ['test-pubkey']]]);

    Livewire::test('association.election.admin', ['election' => $election])
        ->set('elections.0.candidates', $newCandidates)
        ->call('saveElection', 0);

    expect($election->fresh()->candidates)->toBe($newCandidates);
});

// Election Show Tests
it('renders election show component', function () {
    $election = Election::factory()->create();

    Livewire::test('association.election.show', ['election' => $election])
        ->assertStatus(200);
});

it('loads election data on mount in show', function () {
    $election = Election::factory()->create();

    Livewire::test('association.election.show', ['election' => $election])
        ->assertSet('election.id', $election->id);
});

it('handles search in election show', function () {
    $election = Election::factory()->create();
    $pleb1 = EinundzwanzigPleb::factory()->active()->create();
    $pleb2 = EinundzwanzigPleb::factory()->boardMember()->create();

    Livewire::test('association.election.show', ['election' => $election])
        ->set('search', $pleb1->pubkey)
        ->assertSet('plebs', function ($plebs) use ($pleb1) {
            return collect($plebs)->contains('pubkey', $pleb1->pubkey);
        });
});

it('can create vote event', function () {
    $election = Election::factory()->create();
    $pleb = EinundzwanzigPleb::factory()->active()->create();
    $candidatePubkey = 'test-candidate-pubkey';

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.election.show', ['election' => $election])
        ->call('vote', $candidatePubkey, 'presidency', false)
        ->assertSet('signThisEvent', function ($event) use ($candidatePubkey) {
            return str_contains($event, $candidatePubkey);
        });
});

it('checks election closure status', function () {
    $election = Election::factory()->create([
        'end_time' => now()->subDay(),
    ]);

    Livewire::test('association.election.show', ['election' => $election])
        ->call('checkElection')
        ->assertSet('isNotClosed', false);
});

it('displays log for authorized users', function () {
    $allowedPubkey = '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033';
    $pleb = EinundzwanzigPleb::factory()->create(['pubkey' => $allowedPubkey]);
    $election = Election::factory()->create();

    Livewire::test('association.election.show', ['election' => $election])
        ->call('handleNostrLoggedIn', $allowedPubkey)
        ->assertSet('showLog', true);
});
