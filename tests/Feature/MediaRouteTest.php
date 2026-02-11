<?php

it('returns 404 for non-numeric media id', function (string $invalidId) {
    $this->get("/media/{$invalidId}")
        ->assertNotFound();
})->with(['*', 'abc', 'foo-bar']);

it('returns 404 for non-numeric dl id', function (string $invalidId) {
    $this->get("/dl/{$invalidId}")
        ->assertNotFound();
})->with(['*', 'abc', 'foo-bar']);
