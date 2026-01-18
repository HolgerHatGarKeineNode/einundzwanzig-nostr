<?php

use Livewire\Livewire;

it('returns a successful response', function () {
    Livewire::test('association.profile')
        ->assertStatus(200);
});
