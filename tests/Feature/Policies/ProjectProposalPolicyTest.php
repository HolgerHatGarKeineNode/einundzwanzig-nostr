<?php

use App\Auth\NostrUser;
use App\Models\EinundzwanzigPleb;
use App\Models\ProjectProposal;
use Illuminate\Support\Facades\Gate;

// viewAny
it('allows anyone to view any project proposals', function () {
    expect(Gate::forUser(null)->allows('viewAny', ProjectProposal::class))->toBeTrue();
});

it('allows authenticated user to view any project proposals', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('viewAny', ProjectProposal::class))->toBeTrue();
});

// view
it('allows anyone to view a project proposal', function () {
    $project = ProjectProposal::factory()->create();

    expect(Gate::forUser(null)->allows('view', $project))->toBeTrue();
});

// create
it('allows active member with paid membership to create project proposals', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('create', ProjectProposal::class))->toBeTrue();
});

it('denies creation for default (non-member) pleb', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('create', ProjectProposal::class))->toBeFalse();
});

it('denies creation for active member without paid membership', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->create();
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('create', ProjectProposal::class))->toBeFalse();
});

it('denies creation for passive member without paid membership', function () {
    $pleb = EinundzwanzigPleb::factory()->create([
        'association_status' => \App\Enums\AssociationStatus::PASSIVE,
    ]);
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('create', ProjectProposal::class))->toBeFalse();
});

it('allows passive member with paid membership to create project proposals', function () {
    $pleb = EinundzwanzigPleb::factory()->withPaidCurrentYear()->create([
        'association_status' => \App\Enums\AssociationStatus::PASSIVE,
    ]);
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('create', ProjectProposal::class))->toBeTrue();
});

it('denies creation for unauthenticated users', function () {
    expect(Gate::forUser(null)->allows('create', ProjectProposal::class))->toBeFalse();
});

// update
it('allows project creator to update their project proposal', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $pleb->id,
    ]);
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('update', $project))->toBeTrue();
});

it('allows board member to update any project proposal', function () {
    $pleb = EinundzwanzigPleb::factory()->boardMember()->create();
    $project = ProjectProposal::factory()->create();
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('update', $project))->toBeTrue();
});

it('denies non-owner non-board member from updating a project proposal', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('update', $project))->toBeFalse();
});

// delete
it('allows project creator to delete their project proposal', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $pleb->id,
    ]);
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('delete', $project))->toBeTrue();
});

it('allows board member to delete any project proposal', function () {
    $pleb = EinundzwanzigPleb::factory()->boardMember()->create();
    $project = ProjectProposal::factory()->create();
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('delete', $project))->toBeTrue();
});

it('denies non-owner non-board member from deleting a project proposal', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('delete', $project))->toBeFalse();
});

// accept
it('allows board member to accept a project proposal', function () {
    $pleb = EinundzwanzigPleb::factory()->boardMember()->create();
    $project = ProjectProposal::factory()->create();
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('accept', $project))->toBeTrue();
});

it('denies non-board member from accepting a project proposal', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->create();
    $project = ProjectProposal::factory()->create();
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('accept', $project))->toBeFalse();
});

it('denies project creator from accepting their own project proposal', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $pleb->id,
    ]);
    $nostrUser = new NostrUser($pleb->pubkey);

    expect(Gate::forUser($nostrUser)->allows('accept', $project))->toBeFalse();
});
