<?php

use App\Livewire\Forms\ProjectProposalForm;

it('has correct validation rules for all fields', function () {
    $form = new ProjectProposalForm;

    // Test name field - required|min:5
    $form->name = '';
    expect(fn () => $form->validate())->toThrow();

    $form->name = 'short'; // Less than 5 characters
    expect(fn () => $form->validate())->toThrow();

    // Test support_in_sats field - required|numeric|min:21
    $form->name = 'Valid Project';
    $form->support_in_sats = '';
    expect(fn () => $form->validate())->toThrow();

    $form->support_in_sats = 'not-numeric';
    expect(fn () => $form->validate())->toThrow();

    $form->support_in_sats = '20'; // Less than 21
    expect(fn () => $form->validate())->toThrow();

    // Test description field - required|string|min:5
    $form->name = 'Valid Project';
    $form->support_in_sats = '21000';
    $form->description = '';
    expect(fn () => $form->validate())->toThrow();

    $form->description = 'short';
    expect(fn () => $form->validate())->toThrow();

    // Test website field - required|url
    $form->name = 'Valid Project';
    $form->support_in_sats = '21000';
    $form->description = 'Valid description';
    $form->website = 'not-a-url';
    expect(fn () => $form->validate())->toThrow();
});

it('accepts valid project proposal data', function () {
    $form = new ProjectProposalForm;

    $form->name = 'Test Project';
    $form->support_in_sats = '21000';
    $form->description = 'This is a test project description that meets the minimum length requirement.';
    $form->website = 'https://example.com';
    $form->accepted = true;
    $form->sats_paid = 5000;

    $result = $form->validate();
    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

it('validates accepted field as boolean', function () {
    $form = new ProjectProposalForm;
    $form->name = 'Valid Project';
    $form->support_in_sats = '21000';
    $form->description = 'Valid description';
    $form->website = 'https://example.com';

    $form->accepted = 'not-boolean';
    expect(fn () => $form->validate())->toThrow();

    // Test with boolean values
    $form->accepted = false;
    expect($form->accepted)->toBeBool();

    $form->accepted = true;
    expect($form->accepted)->toBeBool();
});

it('validates sats_paid as nullable numeric', function () {
    $form = new ProjectProposalForm;
    $form->name = 'Valid Project';
    $form->support_in_sats = '21000';
    $form->description = 'Valid description';
    $form->website = 'https://example.com';

    // Test with null (should be acceptable)
    $form->sats_paid = null;
    $form->accepted = false;

    $result = $form->validate();
    expect($result)->toBeArray();
    expect($result)->toBeEmpty();

    // Test with numeric
    $form->sats_paid = 'not-numeric';
    expect(fn () => $form->validate())->toThrow();

    $form->sats_paid = 10000;
    $form->accepted = false;
    $result = $form->validate();
    expect($result)->toBeArray();
    expect($result)->toBeEmpty();
});

it('has correct default values', function () {
    $form = new ProjectProposalForm;

    expect($form->name)->toBe('');
    expect($form->support_in_sats)->toBe('');
    expect($form->description)->toBe('');
    expect($form->website)->toBe('');
    expect($form->accepted)->toBeFalse();
    expect($form->sats_paid)->toBe(0);
});
