<?php

use App\Enums\ProjectProposalStatus;
use App\Models\EinundzwanzigPleb;
use App\Models\ProjectProposal;
use App\Models\Vote;
use App\Support\NostrAuth;
use Livewire\Livewire;

/**
 * ProjectProposal::boardPlebIds() memoizes the board's pleb ids in a
 * function-local static variable that lives for the whole PHP process (see
 * app/Models/ProjectProposal.php) — it is computed once and never
 * invalidated, in tests or in production. This file's first test is also
 * the first test in the whole suite that renders the index page, which is
 * the first thing to ever call it. Seeding the same board, in the same
 * npub order, here — ids reset to 1 per test via RefreshDatabase — keeps
 * this (and every later file that repeats the same seeding order) aligned
 * with whatever got cached first. This is a workaround for a real bug
 * reported separately, not a fix for it.
 */
beforeEach(function () {
    collect(config('einundzwanzig.config.current_board'))->each(
        fn (string $npub) => EinundzwanzigPleb::factory()->create(['npub' => $npub])
    );
});

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

it('counts every status regardless of which filter is active', function () {
    ProjectProposal::factory()->count(2)->create();
    ProjectProposal::factory()->create(['sats_paid' => 21000]);

    $component = Livewire::test('association.project-support.index')
        ->call('setFilter', ProjectProposalStatus::Supported->value);

    expect($component->get('statusCounts'))->toBe([
        'all' => 3,
        ProjectProposalStatus::InVoting->value => 2,
        ProjectProposalStatus::Accepted->value => 0,
        ProjectProposalStatus::Rejected->value => 0,
        ProjectProposalStatus::Supported->value => 1,
    ]);
});

it('combines search and status filter', function () {
    $matching = ProjectProposal::factory()->create(['name' => 'Lightning Node Grant']);
    ProjectProposal::factory()->create(['name' => 'Lightning Watchtower Grant', 'sats_paid' => 21000]);
    ProjectProposal::factory()->create(['name' => 'Unrelated Project']);

    Livewire::test('association.project-support.index')
        ->set('search', 'Lightning')
        ->call('setFilter', ProjectProposalStatus::InVoting->value)
        ->assertSet('projects', function ($projects) use ($matching) {
            return $projects->count() === 1 && $projects->contains('id', $matching->id);
        });
});

it('resets search, filter and sort in one call', function () {
    Livewire::test('association.project-support.index')
        ->set('search', 'something')
        ->call('setFilter', ProjectProposalStatus::Supported->value)
        ->call('setSort', 'oldest')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('activeFilter', 'all')
        ->assertSet('sortBy', 'newest');
});

it('paginates beyond the first 12 projects', function () {
    ProjectProposal::factory()->count(15)->create();

    $component = Livewire::test('association.project-support.index');

    expect($component->get('projects')->total())->toBe(15);
    expect($component->get('projects')->count())->toBe(12);
    expect($component->get('projects')->lastPage())->toBe(2);
});

it('can confirm delete', function () {
    $project = ProjectProposal::factory()->create();

    Livewire::test('association.project-support.index')
        ->call('confirmDeleteProject', $project->id)
        ->assertSet('projectToDelete.id', $project->id);
});

it('can delete project', function () {
    $pleb = EinundzwanzigPleb::factory()->boardMember()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $pleb->id,
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.index')
        ->call('confirmDeleteProject', $project->id)
        ->call('delete');

    expect(ProjectProposal::find($project->id))->toBeNull();
});

it('reflects an authenticated nostr session on mount', function () {
    $pleb = EinundzwanzigPleb::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.index')
        ->assertSet('currentPubkey', $pleb->pubkey)
        ->assertSet('isAllowed', true);
});

it('clears state on nostr logout', function () {
    $pleb = EinundzwanzigPleb::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.index')
        ->assertSet('currentPubkey', $pleb->pubkey)
        ->call('handleNostrLogout')
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
        ->set('form.support_in_sats', 21000)
        ->set('form.website', 'https://example.com')
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

    Livewire::test('association.project-support.form.edit', ['projectProposal' => $project])
        ->assertStatus(200);
});

it('denies access to edit when not owner', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.form.edit', ['projectProposal' => $project])
        ->assertSet('isAllowed', false);
});

it('grants access to edit when owner', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $pleb->id,
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.form.edit', ['projectProposal' => $project])
        ->assertSet('isAllowed', true);
});

it('can update project proposal', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $pleb->id,
        'name' => 'Old Name',
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.form.edit', ['projectProposal' => $project])
        ->set('form.name', 'New Name')
        ->set('form.description', 'Updated Description')
        ->set('form.support_in_sats', 21000)
        ->set('form.website', 'https://example.com')
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

    Livewire::test('association.project-support.form.edit', ['projectProposal' => $project])
        ->set('form.name', '')
        ->call('update')
        ->assertHasErrors(['form.name']);
});

// Project Support Show Tests
it('renders project support show component', function () {
    $project = ProjectProposal::factory()->create();

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertStatus(200);
});

it('denies access to show when not authenticated', function () {
    $project = ProjectProposal::factory()->create();

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('isAllowed', false);
});

it('grants access to show when authenticated', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('isAllowed', true);
});

it('displays project details', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create([
        'name' => 'Test Project Name',
        'description' => 'Test Project Description',
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('projectProposal.name', 'Test Project Name')
        ->assertSee('Test Project Name')
        ->assertSee('Test Project Description');
});

it('initializes currentPleb when authenticated', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('currentPleb.id', $pleb->id);
});

it('initializes ownVoteExists to false when no vote exists', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('ownVoteExists', false)
        ->assertSee('Zustimmen')
        ->assertSee('Ablehnen');
});

it('initializes ownVoteExists to true when vote exists', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();
    Vote::create([
        'project_proposal_id' => $project->id,
        'einundzwanzig_pleb_id' => $pleb->id,
        'value' => true,
    ]);

    NostrAuth::login($pleb->pubkey);

    // Eine abgegebene Stimme ist endgültig: canVote (VotePolicy::create)
    // wird false. Das Panel bleibt aber sichtbar und nennt die eigene
    // Stimme — vorher stand dort nur "Du hast bereits abgestimmt", ohne zu
    // sagen, wie. Sichtbarkeit hängt deshalb an isVoter, nicht an canVote.
    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('ownVoteExists', true)
        ->assertSet('canVote', false)
        ->assertSet('isVoter', true)
        ->assertSee('Deine Stimme')
        ->assertSee('Du hast zugestimmt.')
        ->assertSee('Eine abgegebene Stimme lässt sich nicht mehr ändern.')
        ->assertDontSee('Zustimmen')
        ->assertDontSee('Ablehnen');
});

it('shows the own rejection instead of an approval', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();
    Vote::create([
        'project_proposal_id' => $project->id,
        'einundzwanzig_pleb_id' => $pleb->id,
        'value' => false,
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSee('Du hast abgelehnt.')
        ->assertDontSee('Du hast zugestimmt.');
});

it('requires a confirmation modal before a vote is cast', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    // Die Knöpfe lösen nicht direkt aus, sondern öffnen einen Bestätigungsdialog —
    // eine endgültige Stimme darf kein Fehlklick sein.
    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSee('Deine Stimme ist endgültig.')
        ->assertSee('confirm-approve', escape: false)
        ->assertSee('confirm-reject', escape: false);
});

it('can handle approve vote', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->call('handleApprove')
        ->assertHasNoErrors();

    $vote = Vote::query()
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

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->call('handleNotApprove')
        ->assertHasNoErrors();

    $vote = Vote::query()
        ->where('project_proposal_id', $project->id)
        ->where('einundzwanzig_pleb_id', $pleb->id)
        ->first();

    expect($vote)->not->toBeNull()
        ->and($vote->value)->toBeFalse();
});

it('does not throw error when unauthenticated user calls handleApprove', function () {
    $project = ProjectProposal::factory()->create();

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->call('handleApprove')
        ->assertHasNoErrors();

    expect(Vote::where('project_proposal_id', $project->id)->exists())->toBeFalse();
});

it('does not throw error when unauthenticated user calls handleNotApprove', function () {
    $project = ProjectProposal::factory()->create();

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->call('handleNotApprove')
        ->assertHasNoErrors();

    expect(Vote::where('project_proposal_id', $project->id)->exists())->toBeFalse();
});

it('hides voting buttons from unauthenticated users', function () {
    $project = ProjectProposal::factory()->create();

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertDontSee('Zustimmen')
        ->assertDontSee('Ablehnen');
});

// recordPayout / revertPayout

it('lets a board member record a payout, which flips the status to supported', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create(['support_in_sats' => 100000, 'sats_paid' => 0]);

    NostrAuth::login($board->pubkey);

    $component = Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->set('payoutSats', 100000)
        ->call('recordPayout')
        ->assertHasNoErrors();

    // Backed enum cases are invokable in this codebase (ArchTech\Enums\InvokableCases),
    // which makes `assertSet('status', SomeCase)` misfire — Livewire treats the enum
    // instance as a callable predicate instead of a value to compare against.
    expect($component->get('status'))->toBe(ProjectProposalStatus::Supported);
    expect($project->fresh()->sats_paid)->toBe(100000);
});

it('denies recordPayout to a non-board member', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create(['sats_paid' => 0]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->set('payoutSats', 21000)
        ->call('recordPayout')
        ->assertForbidden();

    expect($project->fresh()->sats_paid)->toBe(0);
});

it('denies recordPayout to a guest', function () {
    $project = ProjectProposal::factory()->create(['sats_paid' => 0]);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->set('payoutSats', 21000)
        ->call('recordPayout')
        ->assertForbidden();

    expect($project->fresh()->sats_paid)->toBe(0);
});

it('rejects a zero payout amount', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create(['sats_paid' => 0]);

    NostrAuth::login($board->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->set('payoutSats', 0)
        ->call('recordPayout')
        ->assertHasErrors(['payoutSats']);

    expect($project->fresh()->sats_paid)->toBe(0);
});

it('lets a board member revert a recorded payout', function () {
    // sats_paid is deliberately not fillable (see MassAssignmentProtectionTest)
    // — a payout must be set via direct attribute assignment, not update().
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create();
    $project->sats_paid = 50000;
    $project->save();

    NostrAuth::login($board->pubkey);

    $component = Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->call('revertPayout');

    expect($component->get('status'))->toBe(ProjectProposalStatus::InVoting);
    expect($project->fresh()->sats_paid)->toBe(0);
});

it('denies revertPayout to a non-board member and leaves the payout untouched', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();
    $project->sats_paid = 50000;
    $project->save();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->call('revertPayout')
        ->assertForbidden();

    expect($project->fresh()->sats_paid)->toBe(50000);
});

// canSeeContact / Kontaktangabe auf der Detailseite

it('shows the nostr-dm contact info to the submitter', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $pleb->id,
        'contact_via_nostr_dm' => true,
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canSeeContact', true)
        ->assertSee('Nostr-DM erwünscht');
});

it('hides the contact info from an unrelated logged-in member', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create(['contact_via_nostr_dm' => true]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canSeeContact', false)
        ->assertDontSee('Kontakt zum Einreicher');
});

it('shows "no contact channel" instead of an error when both dm and alternative are unset', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $pleb->id,
        'contact_via_nostr_dm' => false,
        'contact_alternative' => null,
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSee('Kein Kontaktweg hinterlegt.');
});
