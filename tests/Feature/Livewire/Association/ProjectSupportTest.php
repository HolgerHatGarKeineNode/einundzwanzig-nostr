<?php

use App\Enums\ProjectProposalStatus;
use App\Models\EinundzwanzigPleb;
use App\Models\ProjectProposal;
use App\Models\Vote;
use App\Support\NostrAuth;
use Livewire\Livewire;

/**
 * Der Vorstand wird je Test frisch angelegt, weil ProjectProposal::boardPlebIds()
 * die npubs aus der Konfiguration bei jedem Aufruf gegen die Datenbank auflöst.
 * Ohne passende Pleb-Zeilen zählt keine Stimme als Vorstandsstimme.
 *
 * Bewusst KEINE Annahme über Aufrufreihenfolge oder Memoisierung: die Methode
 * cacht absichtlich nicht (ein prozessweiter Cache überlebt bei langlebigen
 * Workern den Request und würde Stimmen verschlucken).
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
    $pleb = EinundzwanzigPleb::factory()->boardMember()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.index')
        ->call('confirmDeleteProject', $project->id)
        ->assertSet('projectToDelete.id', $project->id);
});

it('refuses to open the delete dialog for an unauthorised caller', function () {
    $project = ProjectProposal::factory()->create();

    // confirmDeleteProject ist als Livewire-Endpunkt direkt aufrufbar und darf
    // deshalb nicht bloss über die UI verborgen sein.
    Livewire::test('association.project-support.index')
        ->call('confirmDeleteProject', $project->id)
        ->assertForbidden();
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

// Stimmungsbild der Nicht-Vorstandsmitglieder

it('shows the members sentiment panel even when nobody has voted yet', function () {
    $project = ProjectProposal::factory()->create();

    // Das Panel hing früher an otherVotes->isNotEmpty() und verschwand ganz —
    // das las sich, als könnten Mitglieder nicht abstimmen.
    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSee('Stimmungsbild der Mitglieder')
        ->assertSee('Zählt nicht zur Mehrheit')
        ->assertSee('Noch keine Stimme abgegeben.');
});

it('lets a non-board member vote without affecting the majority', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    $component = Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('voteCountsTowardsMajority', false)
        ->assertSee('Zählt zum Stimmungsbild der Mitglieder, nicht zur Mehrheit.')
        ->call('handleApprove')
        ->assertHasNoErrors();

    expect(Vote::where('project_proposal_id', $project->id)->count())->toBe(1);
    // Die Stimme ist da, der Status bleibt unberührt.
    expect($component->get('status'))->toBe(ProjectProposalStatus::InVoting);
    expect($project->fresh()->boardApprovals())->toBe(0);
});

it('marks a board member vote as counting towards the majority', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($board->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('voteCountsTowardsMajority', true)
        ->assertSee('Zählt zur bindenden Mehrheit des Vorstands.');
});

it('separates board votes from the members sentiment', function () {
    $project = ProjectProposal::factory()->create();
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $member = EinundzwanzigPleb::factory()->create();

    Vote::create(['project_proposal_id' => $project->id, 'einundzwanzig_pleb_id' => $board->id, 'value' => true]);
    Vote::create(['project_proposal_id' => $project->id, 'einundzwanzig_pleb_id' => $member->id, 'value' => true]);

    $component = Livewire::test('association.project-support.show', ['projectProposal' => $project]);

    expect($component->get('boardVotes'))->toHaveCount(1);
    expect($component->get('otherVotes'))->toHaveCount(1);
});

// recordPayout / revertPayout

/**
 * Lässt den Vorstand mit der geforderten Mehrheit zustimmen bzw. ablehnen.
 * Die Anzahl kommt aus boardVoteThreshold(), nie als feste Zahl.
 */
function boardDecides(ProjectProposal $project, bool $inFavour, ?int $count = null): void
{
    $count ??= ProjectProposal::boardVoteThreshold();

    collect(ProjectProposal::boardPlebIds())->take($count)->each(
        fn (int $plebId) => Vote::create([
            'project_proposal_id' => $project->id,
            'einundzwanzig_pleb_id' => $plebId,
            'value' => $inFavour,
        ])
    );
}

it('lets a board member record a payout, which flips the status to supported', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create(['support_in_sats' => 100000, 'sats_paid' => 0]);
    boardDecides($project, inFavour: true);

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

it('refuses a payout while the proposal is still being voted on', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create(['sats_paid' => 0]);
    // Eine Stimme zu wenig für die absolute Mehrheit.
    boardDecides($project, inFavour: true, count: ProjectProposal::boardVoteThreshold() - 1);

    NostrAuth::login($board->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->set('payoutSats', 50000)
        ->call('recordPayout')
        ->assertForbidden();

    expect($project->fresh()->sats_paid)->toBe(0);
});

it('refuses a payout for a rejected proposal', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create(['sats_paid' => 0]);
    boardDecides($project, inFavour: false);

    NostrAuth::login($board->pubkey);

    // Geld darf einem abgelehnten Antrag unter keinen Umständen folgen.
    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->set('payoutSats', 50000)
        ->call('recordPayout')
        ->assertForbidden();

    expect($project->fresh()->sats_paid)->toBe(0);
});

it('hides the payout form and names the missing approvals', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create(['sats_paid' => 0]);

    NostrAuth::login($board->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canPayout', false)
        ->assertSee('Auszahlung erst nach Beschluss')
        ->assertDontSee('Noch nichts ausgezahlt.');
});

it('rejects a zero payout amount', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create(['sats_paid' => 0]);
    boardDecides($project, inFavour: true);

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

// Privater Chatraum: canCreateChatRoom / canSeeChatRoom / storeChatRoom
//
// Modell-Ebene (nostrGroupId(), nostrGroupMemberPubkeys()) und reine
// Policy-Aufrufe leben in tests/Feature/Models/ProjectProposalNostrGroupTest.php
// und tests/Feature/Policies/ProjectProposalChatRoomPolicyTest.php. Hier geht es
// um den Livewire-Endpunkt selbst: storeChatRoom() ist eine öffentliche Methode
// und direkt aufrufbar, unabhängig davon, was die View anzeigt — der Schutz
// muss also am Aufruf greifen, nicht nur am sichtbaren Knopf.

it('allows a board member to see the create-chat-room action when no room exists', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($board->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canCreateChatRoom', true)
        ->assertSee('Chatraum anlegen');
});

it('hides the create-chat-room action from the submitter', function () {
    $submitter = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create(['einundzwanzig_pleb_id' => $submitter->id]);

    NostrAuth::login($submitter->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canCreateChatRoom', false)
        ->assertDontSee('Chatraum anlegen');
});

it('hides the create-chat-room action from an unrelated member', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canCreateChatRoom', false);
});

it('hides the create-chat-room action from a guest', function () {
    $project = ProjectProposal::factory()->create();

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canCreateChatRoom', false);
});

it('lets a board member create the chat room and persists the computed room id', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($board->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->call('storeChatRoom', $project->nostrGroupId())
        ->assertHasNoErrors();

    $fresh = $project->fresh();
    expect($fresh->nostr_group_h)->toBe($project->nostrGroupId());
    expect($fresh->nostr_group_created_at)->not->toBeNull();
});

it('rejects storeChatRoom for the submitter, even with the correctly computed room id — the endpoint itself must refuse, not just the button', function () {
    $submitter = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create(['einundzwanzig_pleb_id' => $submitter->id]);

    NostrAuth::login($submitter->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->call('storeChatRoom', $project->nostrGroupId())
        ->assertForbidden();

    $fresh = $project->fresh();
    expect($fresh->nostr_group_h)->toBeNull();
    expect($fresh->nostr_group_created_at)->toBeNull();
});

it('rejects storeChatRoom for an unrelated member', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->call('storeChatRoom', $project->nostrGroupId())
        ->assertForbidden();

    expect($project->fresh()->nostr_group_h)->toBeNull();
});

it('rejects storeChatRoom for a guest', function () {
    $project = ProjectProposal::factory()->create();

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->call('storeChatRoom', $project->nostrGroupId())
        ->assertForbidden();

    expect($project->fresh()->nostr_group_h)->toBeNull();
});

it('refuses a second storeChatRoom call once the room already exists, without changing the stored value', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($board->pubkey);

    $component = Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->call('storeChatRoom', $project->nostrGroupId())
        ->assertHasNoErrors();

    $storedAfterFirstCall = $project->fresh()->nostr_group_h;
    $createdAtAfterFirstCall = $project->fresh()->nostr_group_created_at;

    // Der zweite Aufruf trifft auf eine bereits vorhandene Raum-ID: die
    // createChatRoom-Policy verlangt ! hasNostrGroup() und verweigert daher —
    // das ist die Idempotenz-Grenze, nicht ein stiller no-op im Handler selbst.
    $component->call('storeChatRoom', $project->nostrGroupId())
        ->assertForbidden();

    $fresh = $project->fresh();
    expect($fresh->nostr_group_h)->toBe($storedAfterFirstCall);
    expect($fresh->nostr_group_created_at->equalTo($createdAtAfterFirstCall))->toBeTrue();
});

it('rejects storeChatRoom when the reported room id does not match the server-computed one, and writes nothing', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($board->pubkey);

    // Der Aufrufer ist berechtigt (Vorstand, kein Raum vorhanden) — die
    // Ablehnung muss also aus dem Abgleich im Handler kommen, nicht aus der
    // Policy.
    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->call('storeChatRoom', 'p'.str_repeat('f', 12))
        ->assertHasNoErrors();

    $fresh = $project->fresh();
    expect($fresh->nostr_group_h)->toBeNull();
    expect($fresh->nostr_group_created_at)->toBeNull();
    expect($fresh->hasNostrGroup())->toBeFalse();
});

it('hides the chat room panel entirely before it exists for a non-board, non-submitter viewer', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canSeeChatRoom', false)
        ->assertSet('canCreateChatRoom', false)
        ->assertDontSee('Chat zum Antrag');
});

/**
 * Der Einreicher faellt durch beide Einzelrechte, solange kein Raum existiert:
 * `viewChatRoom` verlangt einen Raum, `createChatRoom` den Vorstand. Vor der
 * Einfuehrung von `canSeeChatSection` war der Hinweis fuer genau diesen Fall
 * damit toter Code — der Einreicher erfuhr nichts von dem privaten Kanal.
 */
it('tells the submitter about the coming chat room before it exists', function () {
    $submitter = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create(['einundzwanzig_pleb_id' => $submitter->id]);

    NostrAuth::login($submitter->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canSeeChatSection', true)
        ->assertSet('canSeeChatRoom', false)
        ->assertSet('canCreateChatRoom', false)
        ->assertSee('Chat zum Antrag')
        ->assertSee('Der Vorstand legt den Raum bei Nachfragen an');
});

it('keeps the chat section closed for a non-board, non-submitter viewer', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canSeeChatSection', false)
        ->assertDontSee('Chat zum Antrag')
        ->assertDontSee('Der Vorstand legt den Raum bei Nachfragen an');
});

it('shows the existing chat room to a board member', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create(['nostr_group_h' => 'p'.str_repeat('a', 12)]);

    NostrAuth::login($board->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canSeeChatRoom', true)
        ->assertSee('Chat öffnen');
});

it('shows the existing chat room to the submitter', function () {
    $submitter = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create([
        'einundzwanzig_pleb_id' => $submitter->id,
        'nostr_group_h' => 'p'.str_repeat('a', 12),
    ]);

    NostrAuth::login($submitter->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canSeeChatRoom', true)
        ->assertSee('Chat öffnen');
});

it('hides an existing chat room from an unrelated member', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create(['nostr_group_h' => 'p'.str_repeat('a', 12)]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canSeeChatRoom', false)
        ->assertDontSee('Chat zum Antrag');
});

it('hides an existing chat room from a guest', function () {
    $project = ProjectProposal::factory()->create(['nostr_group_h' => 'p'.str_repeat('a', 12)]);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canSeeChatRoom', false)
        ->assertDontSee('Chat zum Antrag');
});

it('delegates chatRoomMemberPubkeys to the model, including submitter and board', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $submitter = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create(['einundzwanzig_pleb_id' => $submitter->id]);

    NostrAuth::login($board->pubkey);

    $component = Livewire::test('association.project-support.show', ['projectProposal' => $project]);

    expect($component->get('chatRoomMemberPubkeys'))->toBe($project->nostrGroupMemberPubkeys());
    expect($component->get('chatRoomMemberPubkeys'))->toContain($submitter->pubkey);
});

// Eingebettete Chat-Insel auf der Detailseite.
//
// Entscheidend ist die SERVER-Seite: Wer den Raum nicht sehen darf, bekommt das
// Insel-Markup gar nicht erst ausgeliefert. Ein rein visuelles Verstecken waere
// wertlos — der Raumname und die Mitglieder-Pubkeys staenden trotzdem im HTML.

it('embeds the chat island for a board member once the room exists', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create();
    $project->nostr_group_h = $project->nostrGroupId();
    $project->nostr_group_created_at = now();
    $project->save();

    NostrAuth::login($board->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canSeeChatRoom', true)
        ->assertSee('projectChatFeed(', false)
        ->assertSee('projectChatRoomFeed(', false);
});

it('embeds the chat island for the submitter once the room exists', function () {
    $submitter = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create(['einundzwanzig_pleb_id' => $submitter->id]);
    $project->nostr_group_h = $project->nostrGroupId();
    $project->nostr_group_created_at = now();
    $project->save();

    NostrAuth::login($submitter->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canSeeChatRoom', true)
        ->assertSee('projectChatFeed(', false);
});

it('leaves no trace of the chat island for an unrelated member', function () {
    $pleb = EinundzwanzigPleb::factory()->create();
    $project = ProjectProposal::factory()->create();
    $project->nostr_group_h = $project->nostrGroupId();
    $project->nostr_group_created_at = now();
    $project->save();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canSeeChatRoom', false)
        ->assertDontSee('projectChatFeed(', false)
        ->assertDontSee('projectChatRoomFeed(', false)
        ->assertDontSee($project->nostrGroupId());
});

it('leaves no trace of the chat island for a guest', function () {
    $project = ProjectProposal::factory()->create();
    $project->nostr_group_h = $project->nostrGroupId();
    $project->nostr_group_created_at = now();
    $project->save();

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSet('canSeeChatRoom', false)
        ->assertDontSee('projectChatFeed(', false)
        ->assertDontSee($project->nostrGroupId());
});

it('keeps the fallback link to the full chat client next to the island', function () {
    $board = EinundzwanzigPleb::query()->where('npub', config('einundzwanzig.config.current_board')[0])->firstOrFail();
    $project = ProjectProposal::factory()->create();
    $project->nostr_group_h = $project->nostrGroupId();
    $project->nostr_group_created_at = now();
    $project->save();

    NostrAuth::login($board->pubkey);

    Livewire::test('association.project-support.show', ['projectProposal' => $project])
        ->assertSee('Chat öffnen')
        ->assertSee('/rooms/'.$project->nostrGroupId(), false);
});
