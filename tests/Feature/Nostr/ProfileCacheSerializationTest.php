<?php

use Illuminate\Support\Facades\Cache;

/**
 * Der Profil-Cache legt rohe kind-0-Events als `stdClass` ab (ProfileCache des
 * Packages). Steht `cache.serializable_classes` auf `false`, liefert der
 * Datenbank-Store — der Store der Produktion — beim Lesen ein
 * `__PHP_Incomplete_Class` statt des Events: kein Miss, sondern ein Treffer
 * voller Attrappen, der 24 Stunden lang gültig bleibt und über
 * GET /nostr/profiles als `{}` beim Browser landet.
 *
 * Der Array-Store der Testumgebung serialisiert nicht und würde den Fehler
 * verbergen — dieser Test prüft deshalb ausdrücklich gegen `database`.
 */
it('gibt ein gecachtes kind-0-Event aus dem Datenbank-Store unversehrt zurück', function () {
    $event = (object) [
        'id' => str_repeat('a', 64),
        'pubkey' => str_repeat('c', 64),
        'created_at' => 1700000000,
        'kind' => 0,
        'tags' => [],
        'content' => json_encode(['name' => 'Vorstand']),
        'sig' => str_repeat('b', 128),
    ];

    Cache::store('database')->put('nostr:profile:'.$event->pubkey, $event, 60);

    $cached = Cache::store('database')->get('nostr:profile:'.$event->pubkey);

    expect($cached)->toBeInstanceOf(stdClass::class)
        ->and($cached->sig)->toBe($event->sig)
        ->and($cached->pubkey)->toBe($event->pubkey);

    // Und so kommt es beim Browser an: vollständig, nicht als leeres Objekt.
    expect(json_decode(json_encode($cached), true))
        ->toHaveKeys(['id', 'pubkey', 'created_at', 'kind', 'tags', 'content', 'sig']);
});

it('lässt weiterhin nur stdClass aus dem Cache zu', function () {
    expect(config('cache.serializable_classes'))->toBe([stdClass::class]);
});
