<?php

use App\Auth\NostrUser;
use App\Models\EinundzwanzigPleb;
use App\Models\ProjectProposal;
use Illuminate\Support\Facades\Gate;

// createChatRoom

it('allows a board member to create the chat room when none exists yet', function () {
    $pleb = EinundzwanzigPleb::factory()->boardMember()->create();
    $project = ProjectProposal::factory()->create();
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('createChatRoom', $project))->toBeTrue();
});

it('denies the submitter (not a board member) from creating the chat room', function () {
    $submitter = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create(['einundzwanzig_pleb_id' => $submitter->id]);
    $nostrUser = new NostrUser($submitter->pubkey);

    expect(Gate::forUser($nostrUser)->allows('createChatRoom', $project))->toBeFalse();
});

it('denies an unrelated pleb from creating the chat room', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('createChatRoom', $project))->toBeFalse();
});

it('denies a guest from creating the chat room', function () {
    $project = ProjectProposal::factory()->create();

    expect(Gate::forUser(null)->allows('createChatRoom', $project))->toBeFalse();
});

it('denies a board member from creating the chat room a second time', function () {
    $pleb = EinundzwanzigPleb::factory()->boardMember()->create();
    $project = ProjectProposal::factory()->create(['nostr_group_h' => 'p'.str_repeat('a', 12)]);
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('createChatRoom', $project))->toBeFalse();
});

// viewChatRoom

it('denies everyone from viewing the chat room before it exists, even a board member', function () {
    $pleb = EinundzwanzigPleb::factory()->boardMember()->create();
    $project = ProjectProposal::factory()->create();
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('viewChatRoom', $project))->toBeFalse();
});

it('allows a board member to view an existing chat room', function () {
    $pleb = EinundzwanzigPleb::factory()->boardMember()->create();
    $project = ProjectProposal::factory()->create(['nostr_group_h' => 'p'.str_repeat('a', 12)]);
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('viewChatRoom', $project))->toBeTrue();
});

it('allows the submitter to view an existing chat room of their own proposal', function () {
    $submitter = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $submitter->id,
        'nostr_group_h' => 'p'.str_repeat('a', 12),
    ]);
    $nostrUser = new NostrUser($submitter->pubkey);

    expect(Gate::forUser($nostrUser)->allows('viewChatRoom', $project))->toBeTrue();
});

it('denies an unrelated pleb from viewing an existing chat room', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create(['nostr_group_h' => 'p'.str_repeat('a', 12)]);
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('viewChatRoom', $project))->toBeFalse();
});

it('denies a guest from viewing an existing chat room', function () {
    $project = ProjectProposal::factory()->create(['nostr_group_h' => 'p'.str_repeat('a', 12)]);

    expect(Gate::forUser(null)->allows('viewChatRoom', $project))->toBeFalse();
});

// resetChatRoom
//
// Reparatur fuer den toten Verweis: Ein auf dem Relay geloeschter Raum
// (kind 9008) bleibt am Antrag stehen und sperrt ihn — createChatRoom verlangt
// einen freien Antrag. Zuruecksetzen loest genau diese Sperre und beruehrt das
// Relay nicht.

it('allows a board member to reset an existing chat room reference', function () {
    $pleb = EinundzwanzigPleb::factory()->boardMember()->create();
    $project = ProjectProposal::factory()->create(['nostr_group_h' => 'p'.str_repeat('a', 12)]);
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('resetChatRoom', $project))->toBeTrue();
});

it('denies a board member from resetting when no chat room is on file — there is nothing to reset', function () {
    $pleb = EinundzwanzigPleb::factory()->boardMember()->create();
    $project = ProjectProposal::factory()->create();
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('resetChatRoom', $project))->toBeFalse();
});

it('denies the submitter from resetting the chat room reference of their own proposal', function () {
    $submitter = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $submitter->id,
        'nostr_group_h' => 'p'.str_repeat('a', 12),
    ]);
    $nostrUser = new NostrUser($submitter->pubkey);

    expect(Gate::forUser($nostrUser)->allows('resetChatRoom', $project))->toBeFalse();
});

it('denies an unrelated pleb from resetting the chat room reference', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create(['nostr_group_h' => 'p'.str_repeat('a', 12)]);
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('resetChatRoom', $project))->toBeFalse();
});

it('denies a guest from resetting the chat room reference', function () {
    $project = ProjectProposal::factory()->create(['nostr_group_h' => 'p'.str_repeat('a', 12)]);

    expect(Gate::forUser(null)->allows('resetChatRoom', $project))->toBeFalse();
});
