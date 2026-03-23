<?php

use App\Models\ProjectProposal;
use Illuminate\Support\Facades\Storage;

it('serves original media via signed route', function () {
    Storage::fake('private');

    $project = ProjectProposal::factory()->create();

    $project->addMedia(
        \Illuminate\Http\UploadedFile::fake()->image('test.jpg', 100, 100)
    )->toMediaCollection('main');

    $media = $project->getFirstMedia('main');

    $url = url()->temporarySignedRoute('media.signed', now()->addMinutes(60), ['media' => $media]);

    $this->get($url)->assertSuccessful();
});

it('serves conversion media via signed route when conversion parameter is provided', function () {
    Storage::fake('private');

    $project = ProjectProposal::factory()->create();

    $project->addMedia(
        \Illuminate\Http\UploadedFile::fake()->image('test.jpg', 500, 500)
    )->toMediaCollection('main');

    $media = $project->getFirstMedia('main');

    $url = url()->temporarySignedRoute('media.signed', now()->addMinutes(60), [
        'media' => $media,
        'conversion' => 'preview',
    ]);

    $this->get($url)->assertSuccessful();
});

it('falls back to original when conversion does not exist', function () {
    Storage::fake('private');

    $project = ProjectProposal::factory()->create();

    $project->addMedia(
        \Illuminate\Http\UploadedFile::fake()->image('test.jpg', 100, 100)
    )->toMediaCollection('main');

    $media = $project->getFirstMedia('main');

    $url = url()->temporarySignedRoute('media.signed', now()->addMinutes(60), [
        'media' => $media,
        'conversion' => 'nonexistent',
    ]);

    $this->get($url)->assertSuccessful();
});

it('rejects unsigned media requests', function () {
    Storage::fake('private');

    $project = ProjectProposal::factory()->create();

    $project->addMedia(
        \Illuminate\Http\UploadedFile::fake()->image('test.jpg', 100, 100)
    )->toMediaCollection('main');

    $media = $project->getFirstMedia('main');

    $this->get("/media/{$media->id}")->assertForbidden();
});

it('generates signed url with conversion parameter', function () {
    Storage::fake('private');

    $project = ProjectProposal::factory()->create();

    $project->addMedia(
        \Illuminate\Http\UploadedFile::fake()->image('test.jpg', 500, 500)
    )->toMediaCollection('main');

    $urlWithoutConversion = $project->getSignedMediaUrl('main');
    $urlWithConversion = $project->getSignedMediaUrl('main', 60, 'preview');

    expect($urlWithoutConversion)->not->toContain('conversion=');
});

it('returns fallback url when no media exists', function () {
    $project = ProjectProposal::factory()->create();

    $url = $project->getSignedMediaUrl('main', 60, 'preview');

    expect($url)->toContain('einundzwanzig-alpha.jpg');
});
