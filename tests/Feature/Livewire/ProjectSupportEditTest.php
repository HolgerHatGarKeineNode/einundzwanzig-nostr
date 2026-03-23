<?php

use App\Enums\AssociationStatus;
use App\Models\EinundzwanzigPleb;
use App\Models\ProjectProposal;
use App\Support\NostrAuth;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->pleb = EinundzwanzigPleb::query()->create([
        'pubkey' => 'test_pubkey_'.Str::random(20),
        'npub' => 'test_npub_'.Str::random(20),
        'association_status' => AssociationStatus::ACTIVE->value,
    ]);

    // Create payment event for the current year
    $this->pleb->paymentEvents()->create([
        'year' => date('Y'),
        'amount' => 21000,
        'paid' => true,
        'event_id' => 'test_event_'.Str::random(40),
    ]);

    $this->project = ProjectProposal::query()->create([
        'einundzwanzig_pleb_id' => $this->pleb->id,
        'name' => 'Original Project',
        'description' => 'Original Description',
        'support_in_sats' => 21000,
        'website' => 'https://original.example.com',
        'accepted' => false,
        'sats_paid' => 0,
    ]);

    // Get board member pubkeys from config
    $this->boardMember = EinundzwanzigPleb::query()->create([
        'pubkey' => 'board_pubkey_'.Str::random(20),
        'npub' => 'board_npub_'.Str::random(20),
        'association_status' => AssociationStatus::HONORARY->value,
    ]);

    // Simulate board member by temporarily updating config for testing
    config(['einundzwanzig.config.current_board' => [$this->boardMember->npub]]);
});

it('renders edit form for authorized project owners', function () {
    NostrAuth::login($this->pleb->pubkey);

    Livewire::test('association.project-support.form.edit', ['projectProposal' => $this->project])
        ->assertStatus(200)
        ->assertSee('Projektförderungs-Antrag bearbeiten')
        ->assertSet('form.name', $this->project->name)
        ->assertSet('form.description', $this->project->description);
});

it('renders edit form for board members', function () {
    NostrAuth::login($this->boardMember->pubkey);

    Livewire::test('association.project-support.form.edit', ['projectProposal' => $this->project])
        ->assertStatus(200)
        ->assertSee('Projektförderungs-Antrag bearbeiten');
});

it('does not render edit form for unauthorized users', function () {
    $unauthorizedPleb = EinundzwanzigPleb::query()->create([
        'pubkey' => 'test_pubkey_'.Str::random(20),
        'npub' => 'test_npub_'.Str::random(20),
        'association_status' => AssociationStatus::ACTIVE->value,
    ]);

    NostrAuth::login($unauthorizedPleb->pubkey);

    Livewire::test('association.project-support.form.edit', ['projectProposal' => $this->project])
        ->assertSet('isAllowed', false);
});

it('validates required name field', function () {
    NostrAuth::login($this->pleb->pubkey);

    Livewire::test('association.project-support.form.edit', ['projectProposal' => $this->project])
        ->set('form.name', '')
        ->set('form.description', 'Test description')
        ->call('update')
        ->assertHasErrors(['form.name']);
});

it('validates required description field', function () {
    NostrAuth::login($this->pleb->pubkey);

    Livewire::test('association.project-support.form.edit', ['projectProposal' => $this->project])
        ->set('form.name', 'Test Project')
        ->set('form.description', '')
        ->call('update')
        ->assertHasErrors(['form.description']);
});

it('updates project proposal successfully', function () {
    NostrAuth::login($this->pleb->pubkey);

    Livewire::test('association.project-support.form.edit', ['projectProposal' => $this->project])
        ->set('form.name', 'Updated Name')
        ->set('form.description', 'Updated Description')
        ->call('update')
        ->assertHasNoErrors();

    $this->project->refresh();
    expect($this->project->name)->toBe('Updated Name');
    expect($this->project->description)->toBe('Updated Description');
});

it('saves accepted and sats_paid when admin updates', function () {
    NostrAuth::login($this->boardMember->pubkey);

    Livewire::test('association.project-support.form.edit', ['projectProposal' => $this->project])
        ->assertSet('isAdmin', true)
        ->set('form.accepted', true)
        ->set('form.sats_paid', 50000)
        ->call('update')
        ->assertHasNoErrors();

    $this->project->refresh();
    expect($this->project->accepted)->toBeTrue();
    expect($this->project->sats_paid)->toBe(50000);
});

it('does not allow non-admin to change accepted and sats_paid', function () {
    NostrAuth::login($this->pleb->pubkey);

    Livewire::test('association.project-support.form.edit', ['projectProposal' => $this->project])
        ->assertSet('isAdmin', false)
        ->set('form.accepted', true)
        ->set('form.sats_paid', 99999)
        ->call('update')
        ->assertHasNoErrors();

    $this->project->refresh();
    expect($this->project->accepted)->toBeFalse();
    expect($this->project->sats_paid)->toBe(0);
});

it('disables update button during save', function () {
    NostrAuth::login($this->pleb->pubkey);

    Livewire::test('association.project-support.form.edit', ['projectProposal' => $this->project])
        ->set('form.name', 'Test')
        ->set('form.description', 'Test')
        ->call('update')
        ->assertSeeHtml('wire:loading');
});
