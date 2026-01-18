<?php

use App\Enums\AssociationStatus;
use App\Models\EinundzwanzigPleb;
use App\Models\ProjectProposal;
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
    ]);

    // Get board member pubkeys from config
    $boardPubkeys = config('einundzwanzig.config.current_board', []);
    $this->boardMember = EinundzwanzigPleb::query()->create([
        'pubkey' => 'board_pubkey_'.Str::random(20),
        'npub' => 'board_npub_'.Str::random(20),
        'association_status' => AssociationStatus::HONORARY->value,
    ]);

    // Simulate board member by temporarily updating config for testing
    config(['einundzwanzig.config.current_board' => [$this->boardMember->npub]]);
});

it('renders edit form for authorized project owners', function () {
    Livewire::actingAs($this->pleb)
        ->test('association.project-support.form.edit', ['project' => $this->project])
        ->assertStatus(200)
        ->assertSee('Projektförderung bearbeiten')
        ->assertSet('form.name', $this->project->name)
        ->assertSet('form.description', $this->project->description);
});

it('renders edit form for board members', function () {
    Livewire::actingAs($this->boardMember)
        ->test('association.project-support.form.edit', ['project' => $this->project])
        ->assertStatus(200)
        ->assertSee('Projektförderung bearbeiten');
});

it('does not render edit form for unauthorized users', function () {
    $unauthorizedPleb = EinundzwanzigPleb::query()->create([
        'pubkey' => 'test_pubkey_'.Str::random(20),
        'npub' => 'test_npub_'.Str::random(20),
        'association_status' => AssociationStatus::ACTIVE->value,
    ]);

    Livewire::actingAs($unauthorizedPleb)
        ->test('association.project-support.form.edit', ['project' => $this->project])
        ->assertSet('isAllowed', false);
});

it('validates required name field', function () {
    Livewire::actingAs($this->pleb)
        ->test('association.project-support.form.edit', ['project' => $this->project])
        ->set('form.name', '')
        ->set('form.description', 'Test description')
        ->call('update')
        ->assertHasErrors(['form.name']);
});

it('validates required description field', function () {
    Livewire::actingAs($this->pleb)
        ->test('association.project-support.form.edit', ['project' => $this->project])
        ->set('form.name', 'Test Project')
        ->set('form.description', '')
        ->call('update')
        ->assertHasErrors(['form.description']);
});

it('updates project proposal successfully', function () {
    Livewire::actingAs($this->pleb)
        ->test('association.project-support.form.edit', ['project' => $this->project])
        ->set('form.name', 'Updated Name')
        ->set('form.description', 'Updated Description')
        ->call('update')
        ->assertHasNoErrors()
        ->assertRedirect(route('association.projectSupport.item', $this->project));

    $this->project->refresh();
    expect($this->project->name)->toBe('Updated Name');
    expect($this->project->description)->toBe('Updated Description');
});

it('disables update button during save', function () {
    Livewire::actingAs($this->pleb)
        ->test('association.project-support.form.edit', ['project' => $this->project])
        ->set('form.name', 'Test')
        ->set('form.description', 'Test')
        ->call('update')
        ->assertSeeHtml('wire:loading');
});
