<?php

use App\Models\EinundzwanzigPleb;
use App\Support\NostrAuth;
use Livewire\Livewire;

const ALLOWED_ADMIN_PUBKEY = '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033';

it('denies access to unauthorized users', function () {
    $pleb = EinundzwanzigPleb::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.members.admin')
        ->assertSet('isAllowed', false)
        ->assertSee('Mitglieder können nicht bearbeitet werden');
});

it('grants access to authorized pubkeys', function () {
    $allowedPubkeys = [
        '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033',
        '430169631f2f0682c60cebb4f902d68f0c71c498fd1711fd982f052cf1fd4279',
        '7acf30cf60b85c62b8f654556cc21e4016df8f5604b3b6892794f88bb80d7a1d',
        'f240be2b684f85cc81566f2081386af81d7427ea86250c8bde6b7a8500c761ba',
        '19e358b8011f5f4fc653c565c6d4c2f33f32661f4f90982c9eedc292a8774ec3',
        'acbcec475a1a4f9481939ecfbd1c3d111f5b5a474a39ae039bbc720fdd305bec',
    ];

    $pleb = EinundzwanzigPleb::factory()->create([
        'pubkey' => $allowedPubkeys[0],
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.members.admin')
        ->assertSet('isAllowed', true);
});

it('reflects an authorized nostr session on mount', function () {
    $allowedPubkey = '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033';
    EinundzwanzigPleb::factory()->create([
        'pubkey' => $allowedPubkey,
    ]);

    NostrAuth::login($allowedPubkey);

    Livewire::test('association.members.admin')
        ->assertSet('isAllowed', true)
        ->assertSet('currentPubkey', $allowedPubkey);
});

it('clears state on nostr logout', function () {
    $allowedPubkey = '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033';
    EinundzwanzigPleb::factory()->create([
        'pubkey' => $allowedPubkey,
    ]);

    NostrAuth::login($allowedPubkey);

    Livewire::test('association.members.admin')
        ->call('handleNostrLoggedOut')
        ->assertSet('isAllowed', false)
        ->assertSet('currentPubkey', null);
});

it('displays einundzwanzig pleb table when authorized', function () {
    $allowedPubkey = '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033';
    $pleb = EinundzwanzigPleb::factory()->create([
        'pubkey' => $allowedPubkey,
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.members.admin')
        ->assertSet('isAllowed', true)
        ->assertSee('einundzwanzig-pleb-table');
});

it('does not load the member list for unauthorized visitors', function () {
    EinundzwanzigPleb::factory()->count(3)->create();

    Livewire::test('association.members.admin')
        ->assertSet('isAllowed', false)
        ->assertSet('plebs', []);
});

it('forbids guests from exporting the member CSV', function () {
    Livewire::test('association.members.admin')
        ->call('exportCsv')
        ->assertForbidden();
});

it('forbids unauthorized members from exporting the member CSV', function () {
    $pleb = EinundzwanzigPleb::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.members.admin')
        ->call('exportCsv')
        ->assertForbidden();
});

it('forbids unauthorized members from accepting an application', function () {
    $pleb = EinundzwanzigPleb::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.members.admin')
        ->call('acceptPleb')
        ->assertForbidden();
});

it('forbids unauthorized members from rejecting an application', function () {
    $pleb = EinundzwanzigPleb::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.members.admin')
        ->call('deletePleb')
        ->assertForbidden();
});

it('lets an authorized member pass the authorization guard', function () {
    $pleb = EinundzwanzigPleb::factory()->create([
        'pubkey' => ALLOWED_ADMIN_PUBKEY,
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.members.admin')
        ->call('acceptPleb')
        ->assertStatus(200)
        ->assertHasNoErrors();
});
