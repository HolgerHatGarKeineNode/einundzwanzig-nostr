<?php

use App\Auth\NostrUser;
use App\Models\EinundzwanzigPleb;
use App\Models\ProjectProposal;
use App\Models\Vote;
use Illuminate\Support\Facades\Gate;

// create
it('allows authenticated pleb to create a vote for a project proposal', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('create', [Vote::class, $project]))->toBeTrue();
});

it('denies vote creation if pleb has already voted on the proposal', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    Vote::create([
        'project_proposal_id' => $project->id,
        'einundzwanzig_pleb_id' => $pleb->id,
        'value' => true,
    ]);

    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('create', [Vote::class, $project]))->toBeFalse();
});

it('denies vote creation for unauthenticated users', function () {
    $project = ProjectProposal::factory()->create();

    expect(Gate::forUser(null)->allows('create', [Vote::class, $project]))->toBeFalse();
});

// update
it('allows vote owner to update their vote', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();
    $vote = Vote::create([
        'project_proposal_id' => $project->id,
        'einundzwanzig_pleb_id' => $pleb->id,
        'value' => true,
    ]);

    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('update', $vote))->toBeTrue();
});

it('denies non-owner from updating a vote', function () {
    $owner = EinundzwanzigPleb::factory()->create();
    $otherPleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();
    $vote = Vote::create([
        'project_proposal_id' => $project->id,
        'einundzwanzig_pleb_id' => $owner->id,
        'value' => true,
    ]);

    $nostrUser = new NostrUser($otherPleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('update', $vote))->toBeFalse();
});

// delete
it('allows vote owner to delete their vote', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();
    $vote = Vote::create([
        'project_proposal_id' => $project->id,
        'einundzwanzig_pleb_id' => $pleb->id,
        'value' => false,
    ]);

    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('delete', $vote))->toBeTrue();
});

it('denies non-owner from deleting a vote', function () {
    $owner = EinundzwanzigPleb::factory()->create();
    $otherPleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();
    $vote = Vote::create([
        'project_proposal_id' => $project->id,
        'einundzwanzig_pleb_id' => $owner->id,
        'value' => true,
    ]);

    $nostrUser = new NostrUser($otherPleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('delete', $vote))->toBeFalse();
});
