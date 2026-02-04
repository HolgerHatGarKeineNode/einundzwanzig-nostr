<?php

use App\Models\EinundzwanzigPleb;
use App\Models\ProjectProposal;
use App\Support\NostrAuth;
use Livewire\Livewire;

it('loads projects on mount', function () {
    $project1 = ProjectProposal::factory()->create();
    $project2 = ProjectProposal::factory()->create();

    Livewire::test('association.project-support.index')
        ->assertSet('projects', function ($projects) {
            return $projects->count() >= 2;
        });
});

it('can search projects', function () {
    $project = ProjectProposal::factory()->create(['name' => 'Unique Project Name']);

    Livewire::test('association.project-support.index')
        ->set('search', 'Unique')
        ->assertSet('projects', function ($projects) use ($project) {
            return $projects->contains('id', $project->id);
        });
});

it('can filter projects', function () {
    Livewire::test('association.project-support.index')
        ->call('setFilter', 'new')
        ->assertSet('activeFilter', 'new');
});

it('can confirm delete', function () {
    $project = ProjectProposal::factory()->create();

    Livewire::test('association.project-support.index')
        ->call('confirmDelete', $project->id)
        ->assertSet('confirmDeleteId', $project->id);
});

it('can delete project', function () {
    $pleb = EinundzwanzigPleb::factory()->boardMember()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $pleb->id,
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.index')
        ->set('confirmDeleteId', $project->id)
        ->call('delete');

    expect(ProjectProposal::find($project->id))->toBeNull();
});

it('handles nostr login', function () {
    $pleb = EinundzwanzigPleb::factory()->create();

    Livewire::test('association.project-support.index')
        ->call('handleNostrLoggedIn', $pleb->pubkey)
        ->assertSet('currentPubkey', $pleb->pubkey)
        ->assertSet('isAllowed', true);
});

it('handles nostr logout', function () {
    $pleb = EinundzwanzigPleb::factory()->create();

    Livewire::test('association.project-support.index')
        ->call('handleNostrLoggedIn', $pleb->pubkey)
        ->call('handleNostrLoggedOut')
        ->assertSet('currentPubkey', null)
        ->assertSet('isAllowed', false);
});

it('denies access to create when not authenticated', function () {
    Livewire::test('association.project-support.form.create')
        ->assertSet('isAllowed', false)
        ->assertSee('Projektförderung kann nicht angelegt werden');
});

it('denies access to create when pleb has not paid', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.form.create')
        ->assertSet('isAllowed', false);
});

it('grants access to create when pleb is active and paid', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.form.create')
        ->assertSet('isAllowed', true);
});

it('can create project proposal', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.form.create')
        ->set('form.name', 'Test Project')
        ->set('form.description', 'Test Description')
        ->call('save')
        ->assertHasNoErrors();

    expect(ProjectProposal::where('name', 'Test Project')->exists())->toBeTrue();
});

it('validates project proposal creation', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.form.create')
        ->call('save')
        ->assertHasErrors(['form.name', 'form.description']);
});

// Project Support Edit Tests
it('renders project support edit component', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $pleb->id,
    ]);

    Livewire::test('association.project-support.form.edit', ['project' => $project])
        ->assertStatus(200);
});

it('denies access to edit when not owner', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.form.edit', ['project' => $project])
        ->assertSet('isAllowed', false);
});

it('grants access to edit when owner', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $pleb->id,
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.form.edit', ['project' => $project])
        ->assertSet('isAllowed', true);
});

it('can update project proposal', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $pleb->id,
        'name' => 'Old Name',
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.form.edit', ['project' => $project])
        ->set('form.name', 'New Name')
        ->set('form.description', 'Updated Description')
        ->call('update')
        ->assertHasNoErrors();

    expect($project->fresh()->name)->toBe('New Name');
});

it('validates project proposal update', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $pleb->id,
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.form.edit', ['project' => $project])
        ->set('form.name', '')
        ->call('update')
        ->assertHasErrors(['form.name']);
});

// Project Support Show Tests
it('renders project support show component', function () {
    $project = ProjectProposal::factory()->create();

    Livewire::test('association.project-support.show', ['project' => $project])
        ->assertStatus(200);
});

it('denies access to show when not authenticated', function () {
    $project = ProjectProposal::factory()->create();

    Livewire::test('association.project-support.show', ['project' => $project])
        ->assertSet('isAllowed', false)
        ->assertSee('Zugriff auf Projektförderung nicht möglich');
});

it('grants access to show when authenticated', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['project' => $project])
        ->assertSet('isAllowed', true);
});

it('displays project details', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create([
        'name' => 'Test Project Name',
        'description' => 'Test Project Description',
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['project' => $project])
        ->assertSet('project.name', 'Test Project Name')
        ->assertSee('Test Project Name')
        ->assertSee('Test Project Description');
});

it('initializes currentPleb when authenticated', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['project' => $project])
        ->assertSet('currentPleb.id', $pleb->id);
});

it('initializes ownVoteExists to false when no vote exists', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['project' => $project])
        ->assertSet('ownVoteExists', false)
        ->assertSee('Zustimmen')
        ->assertSee('Ablehnen');
});

it('initializes ownVoteExists to true when vote exists', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();
    \App\Models\Vote::create([
        'project_proposal_id' => $project->id,
        'einundzwanzig_pleb_id' => $pleb->id,
        'value' => true,
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['project' => $project])
        ->assertSet('ownVoteExists', true)
        ->assertDontSee('Zustimmen')
        ->assertDontSee('Ablehnen')
        ->assertSee('Du hast bereits abgestimmt.');
});

it('can handle approve vote', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['project' => $project])
        ->call('handleApprove')
        ->assertHasNoErrors();

    $vote = \App\Models\Vote::query()
        ->where('project_proposal_id', $project->id)
        ->where('einundzwanzig_pleb_id', $pleb->id)
        ->first();

    expect($vote)->not->toBeNull()
        ->and($vote->value)->toBeTrue();
});

it('can handle not approve vote', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['project' => $project])
        ->call('handleNotApprove')
        ->assertHasNoErrors();

    $vote = \App\Models\Vote::query()
        ->where('project_proposal_id', $project->id)
        ->where('einundzwanzig_pleb_id', $pleb->id)
        ->first();

    expect($vote)->not->toBeNull()
        ->and($vote->value)->toBeFalse();
});

it('does not throw error when unauthenticated user calls handleApprove', function () {
    $project = ProjectProposal::factory()->create();

    Livewire::test('association.project-support.show', ['projectProposal' => $project->slug])
        ->call('handleApprove')
        ->assertHasNoErrors();

    expect(\App\Models\Vote::where('project_proposal_id', $project->id)->exists())->toBeFalse();
});

it('does not throw error when unauthenticated user calls handleNotApprove', function () {
    $project = ProjectProposal::factory()->create();

    Livewire::test('association.project-support.show', ['projectProposal' => $project->slug])
        ->call('handleNotApprove')
        ->assertHasNoErrors();

    expect(\App\Models\Vote::where('project_proposal_id', $project->id)->exists())->toBeFalse();
});

it('hides voting buttons from unauthenticated users', function () {
    $project = ProjectProposal::factory()->create();

    Livewire::test('association.project-support.show', ['projectProposal' => $project->slug])
        ->assertDontSee('Zustimmen')
        ->assertDontSee('Ablehnen');
});
