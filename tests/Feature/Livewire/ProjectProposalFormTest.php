<?php

use App\Models\EinundzwanzigPleb;
use App\Support\NostrAuth;
use Livewire\Livewire;

it('has correct validation rules for all fields', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();

    NostrAuth::login($pleb->pubkey);

    // Test name field - required
    Livewire::test('association.project-support.form.create')
        ->set('form.name', '')
        ->set('form.description', 'Valid description text')
        ->set('form.support_in_sats', 21000)
        ->set('form.website', 'https://example.com')
        ->call('save')
        ->assertHasErrors(['form.name']);

    // Test support_in_sats field - required|integer|min:0
    Livewire::test('association.project-support.form.create')
        ->set('form.name', 'Valid Project')
        ->set('form.description', 'Valid description text')
        ->set('form.support_in_sats', '')
        ->set('form.website', 'https://example.com')
        ->call('save')
        ->assertHasErrors(['form.support_in_sats']);

    // Test description field - required
    Livewire::test('association.project-support.form.create')
        ->set('form.name', 'Valid Project')
        ->set('form.description', '')
        ->set('form.support_in_sats', 21000)
        ->set('form.website', 'https://example.com')
        ->call('save')
        ->assertHasErrors(['form.description']);

    // Test website field - required|url
    Livewire::test('association.project-support.form.create')
        ->set('form.name', 'Valid Project')
        ->set('form.description', 'Valid description text')
        ->set('form.support_in_sats', 21000)
        ->set('form.website', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['form.website']);
});

it('accepts valid project proposal data', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.form.create')
        ->set('form.name', 'Test Project')
        ->set('form.support_in_sats', 21000)
        ->set('form.description', 'This is a test project description that meets the minimum length requirement.')
        ->set('form.website', 'https://example.com')
        ->call('save')
        ->assertHasNoErrors();
});

it('validates accepted field as boolean', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.form.create')
        ->set('form.name', 'Valid Project')
        ->set('form.support_in_sats', 21000)
        ->set('form.description', 'Valid description text')
        ->set('form.website', 'https://example.com')
        ->set('form.accepted', false)
        ->call('save')
        ->assertHasNoErrors();
});

it('validates sats_paid as nullable numeric', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();

    NostrAuth::login($pleb->pubkey);

    // Test with null (should be acceptable)
    Livewire::test('association.project-support.form.create')
        ->set('form.name', 'Valid Project')
        ->set('form.support_in_sats', 21000)
        ->set('form.description', 'Valid description text')
        ->set('form.website', 'https://example.com')
        ->set('form.sats_paid', 0)
        ->set('form.accepted', false)
        ->call('save')
        ->assertHasNoErrors();
});

it('has correct default values', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.project-support.form.create')
        ->assertSet('form.name', '')
        ->assertSet('form.support_in_sats', '')
        ->assertSet('form.description', '')
        ->assertSet('form.website', '')
        ->assertSet('form.accepted', false)
        ->assertSet('form.sats_paid', 0);
});
