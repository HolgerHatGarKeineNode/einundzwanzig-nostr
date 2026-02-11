<?php

use App\Enums\AssociationStatus;
use App\Enums\NewsCategory;
use App\Models\EinundzwanzigPleb;
use App\Models\Notification;
use App\Support\NostrAuth;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('private');
});

it('denies access when pleb has insufficient association status', function () {
    $pleb = EinundzwanzigPleb::factory()->create([
        'association_status' => AssociationStatus::PASSIVE,
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.news')
        ->assertSet('isAllowed', false);
});

it('denies access when pleb has not paid for current year', function () {
    $pleb = EinundzwanzigPleb::factory()->create([
        'association_status' => AssociationStatus::ACTIVE,
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.news')
        ->assertSet('isAllowed', false);
});

it('grants access when pleb is active and has paid', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.news')
        ->assertSet('isAllowed', true);
});

it('allows board member to edit news', function () {
    $pleb = EinundzwanzigPleb::factory()->boardMember()->withPaidCurrentYear()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.news')
        ->assertSet('canEdit', true);
});

it('can create news entry with pdf', function () {
    $pleb = EinundzwanzigPleb::factory()->boardMember()->withPaidCurrentYear()->create();

    NostrAuth::login($pleb->pubkey);

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
    // Write PDF magic bytes to the temp file so Spatie media library detects correct MIME
    file_put_contents($file->getPathname(), '%PDF-1.4 fake pdf content for testing');

    Livewire::test('association.news')
        ->set('file', $file)
        ->set('form.category', (string) NewsCategory::Organisation->value)
        ->set('form.name', 'Test News')
        ->set('form.description', 'Test Description')
        ->call('save')
        ->assertHasNoErrors();

    expect(Notification::where('name', 'Test News')->exists())->toBeTrue();
});

it('validates news entry creation', function () {
    $pleb = EinundzwanzigPleb::factory()->boardMember()->withPaidCurrentYear()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.news')
        ->call('save')
        ->assertHasErrors(['file', 'form.category', 'form.name']);
});

it('can delete news entry', function () {
    $pleb = EinundzwanzigPleb::factory()->boardMember()->withPaidCurrentYear()->create();
    $news = Notification::factory()->create([
        'einundzwanzig_pleb_id' => $pleb->id,
    ]);

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.news')
        ->call('confirmDelete', $news->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Notification::find($news->id))->toBeNull();
});

it('displays news list', function () {
    $pleb = EinundzwanzigPleb::factory()->active()->withPaidCurrentYear()->create();
    $news1 = Notification::factory()->create();
    $news2 = Notification::factory()->create();

    NostrAuth::login($pleb->pubkey);

    Livewire::test('association.news')
        ->assertSet('isAllowed', true)
        ->assertSee($news1->name)
        ->assertSee($news2->name);
});
