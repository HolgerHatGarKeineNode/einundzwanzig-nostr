<?php

use App\Models\EinundzwanzigPleb;
use App\Support\NostrAuth;
use Livewire\Livewire;

it('denies access to unauthorized users', function () {
    $pleb = EinundzwanzigPleb::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.members.admin')
        ->assertSet('isAllowed', false)
        ->assertSee('Du bist nicht berechtigt, Mitglieder zu bearbeiten.');
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

it('handles nostr login for authorized user', function () {
    $allowedPubkey = '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033';
    $pleb = EinundzwanzigPleb::factory()->create([
        'pubkey' => $allowedPubkey,
    ]);

    Livewire::test('association.members.admin')
        ->call('handleNostrLoggedIn', $allowedPubkey)
        ->assertSet('isAllowed', true)
        ->assertSet('currentPubkey', $allowedPubkey);
});

it('handles nostr logout', function () {
    $allowedPubkey = '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033';
    $pleb = EinundzwanzigPleb::factory()->create([
        'pubkey' => $allowedPubkey,
    ]);

    Livewire::test('association.members.admin')
        ->call('handleNostrLoggedIn', $allowedPubkey)
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
