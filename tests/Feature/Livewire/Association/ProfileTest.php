<?php

use App\Enums\AssociationStatus;
use App\Models\EinundzwanzigPleb;
use App\Support\NostrAuth;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('handles nostr login correctly', function () {
    $pleb = EinundzwanzigPleb::factory()->create();

    Livewire::test('association.profile')
        ->call('handleNostrLoggedIn', $pleb->pubkey)
        ->assertSet('currentPubkey', $pleb->pubkey)
        ->assertSet('currentPleb.pubkey', $pleb->pubkey);
});

it('handles nostr logout correctly', function () {
    $pleb = EinundzwanzigPleb::factory()->create();

    Livewire::test('association.profile')
        ->call('handleNostrLoggedIn', $pleb->pubkey)
        ->call('handleNostrLoggedOut')
        ->assertSet('currentPubkey', null)
        ->assertSet('currentPleb', null);
});

it('can save email address', function () {
    $pleb = EinundzwanzigPleb::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.profile')
        ->set('email', 'test@example.com')
        ->call('saveEmail')
        ->assertHasNoErrors();

    expect($pleb->fresh()->email)->toBe('test@example.com');
});

it('validates email format', function () {
    $pleb = EinundzwanzigPleb::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.profile')
        ->set('email', 'invalid-email')
        ->call('saveEmail')
        ->assertHasErrors(['email']);
});

it('can save nip05 handle', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.profile')
        ->set('nip05Handle', 'user@example.com')
        ->call('saveNip05Handle')
        ->assertHasNoErrors();

    expect($pleb->fresh()->nip05_handle)->toBe('user@example.com');
});

it('validates nip05 handle format', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.profile')
        ->set('nip05Handle', 'not-an-email')
        ->call('saveNip05Handle')
        ->assertHasErrors(['nip05Handle']);
});

it('validates nip05 handle uniqueness', function () {
    $pleb1 = EinundzwanzigPleb::factory()->active()->create([
        'nip05_handle' => 'taken@example.com',
    ]);

    $pleb2 = EinundzwanzigPleb::factory()->active()->create();

    NostrAuth::login($pleb2->pubkey);

    Livewire::test('association.profile')
        ->set('nip05Handle', 'taken@example.com')
        ->call('saveNip05Handle')
        ->assertHasErrors(['nip05Handle']);
});

it('can save null nip05 handle', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->create([
        'nip05_handle' => 'old@example.com',
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.profile')
        ->set('nip05Handle', null)
        ->call('saveNip05Handle')
        ->assertHasNoErrors();

    expect($pleb->fresh()->nip05_handle)->toBeNull();
});

it('can update no email preference', function () {
    $pleb = EinundzwanzigPleb::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.profile')
        ->set('no', true)
        ->assertSet('showEmail', false);

    expect($pleb->fresh()->no_email)->toBeTrue();
});

it('can save membership application', function () {
    $pleb = EinundzwanzigPleb::factory()->create([
        'association_status' => AssociationStatus::DEFAULT,
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.profile')
        ->set('form.check', true)
        ->call('save', AssociationStatus::PASSIVE->value)
        ->assertHasNoErrors();

    expect($pleb->fresh()->association_status)->toBe(AssociationStatus::PASSIVE);
});

it('creates payment event when pleb becomes active', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.profile')
        ->assertSet('amountToPay', config('app.env') === 'production' ? 21000 : 1);

    expect($pleb->paymentEvents()->count())->toBeGreaterThan(0);
});

it('displays paid status for current year', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.profile')
        ->call('listenForPayment')
        ->assertSet('currentYearIsPaid', true);
});

it('can initiate payment', function () {
    Http::fake([
        'pay.einundzwanzig.space/*' => Http::response([
            'id' => 'invoice123',
            'checkoutLink' => 'https://pay.einundzwanzig.space/checkout/invoice123',
        ], 200),
    ]);

    $pleb = EinundzwanzigPleb::factory()->active()->create();

    NostrAuth::login($pleb->pubkey);

    $response = Livewire::test('association.profile')
        ->call('pay', 'test-comment');

    $response->assertRedirect();
});
