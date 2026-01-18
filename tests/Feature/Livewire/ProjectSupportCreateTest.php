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
});

it('renders create form for authorized users', function () {
    Livewire::actingAs($this->pleb)
        ->test('association.project-support.form.create')
        ->assertStatus(200)
        ->assertSee('Projektförderung anlegen')
        ->assertSeeLivewire('association.project-support.form.create');
});

it('does not render create form for unauthorized users', function () {
    $unauthorizedPleb = EinundzwanzigPleb::query()->create([
        'pubkey' => 'test_pubkey_'.Str::random(20),
        'npub' => 'test_npub_'.Str::random(20),
        'association_status' => AssociationStatus::DEFAULT->value,
    ]);

    Livewire::actingAs($unauthorizedPleb)
        ->test('association.project-support.form.create')
        ->assertSet('isAllowed', false)
        ->assertDontSee('Projektförderung anlegen');
});

it('validates required name field', function () {
    Livewire::actingAs($this->pleb)
        ->test('association.project-support.form.create')
        ->set('form.name', '')
        ->set('form.description', 'Test description')
        ->call('save')
        ->assertHasErrors(['form.name']);
});

it('validates name max length', function () {
    Livewire::actingAs($this->pleb)
        ->test('association.project-support.form.create')
        ->set('form.name', Str::random(300))
        ->set('form.description', 'Test description')
        ->call('save')
        ->assertHasErrors(['form.name']);
});

it('validates required description field', function () {
    Livewire::actingAs($this->pleb)
        ->test('association.project-support.form.create')
        ->set('form.name', 'Test Project')
        ->set('form.description', '')
        ->call('save')
        ->assertHasErrors(['form.description']);
});

it('creates project proposal successfully', function () {
    Livewire::actingAs($this->pleb)
        ->test('association.project-support.form.create')
        ->set('form.name', 'Test Project')
        ->set('form.description', 'This is a test project for unit testing purposes.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('association.projectSupport'));

    expect(ProjectProposal::count())->toBe(1);
    $project = ProjectProposal::first();
    expect($project->name)->toBe('Test Project');
    expect($project->description)->toBe('This is a test project for unit testing purposes.');
});

it('associates project proposal with current pleb', function () {
    Livewire::actingAs($this->pleb)
        ->test('association.project-support.form.create')
        ->set('form.name', 'Test Project')
        ->set('form.description', 'Test description')
        ->call('save')
        ->assertHasNoErrors();

    $project = ProjectProposal::first();
    expect($project->einundzwanzig_pleb_id)->toBe($this->pleb->id);
});
