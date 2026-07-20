<?php

declare(strict_types=1);

/**
 * Legt ein kind-0 des Wegwerf-Antragstellers in den SERVER-Cache — und nur dort.
 *
 * Warum es das braucht: profile-seed.spec.js prüft, ob GET /nostr/profiles einen
 * Namen in den eingebetteten Chat bringt. Die Wegwerf-Schlüssel des E2E-Aufbaus
 * haben auf keinem öffentlichen Indexer ein Profil, und eines dorthin zu
 * publizieren wäre Müll in fremder Infrastruktur.
 *
 * Das Event wird deshalb lokal signiert und ausschließlich in unseren Cache
 * geschrieben — es geht an KEIN Relay. Genau das macht den Test aussagekräftig:
 * Erscheint der Name im Verlauf, kann er nur über den neuen Endpunkt gekommen
 * sein, denn die Live-Auflösung über den Space-Relay findet dieses Event nicht.
 *
 * Aufruf (gegen die DB-KOPIE, nie gegen die Arbeitsdatenbank):
 *   DB_DATABASE=/tmp/e2e-verein.sqlite php tests/e2e/support/seed-submitter-profile.php
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
use swentel\nostr\Event\Event;
use swentel\nostr\Key\Key;
use swentel\nostr\Sign\Sign;

require __DIR__.'/../../../vendor/autoload.php';

$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/** Derselbe Schlüssel wie SUBMITTER_SEC in tests/e2e/support/local-nip07.js. */
$secret = 'b7550c0b4c20e479e317ce4fb9bb5c144577f772fce12e48815fb5d71c637781';
$pubkey = (new Key)->getPublicKey($secret);

$event = new Event;
$event->setKind(0);
$event->setTags([]);
$event->setContent(json_encode(['name' => 'E2E Antragsteller']));
$event->setCreatedAt(time());
(new Sign)->signEvent($event, $secret);

$raw = json_decode(json_encode($event->toArray()));

if (! (new Event)->verify($raw)) {
    fwrite(STDERR, "Signatur des erzeugten kind-0 ist ungültig.\n");
    exit(1);
}

Cache::put('nostr:profile:'.$pubkey, $raw, 86400);

$readBack = Cache::get('nostr:profile:'.$pubkey);

if (! $readBack instanceof stdClass) {
    fwrite(STDERR, "Cache liefert kein stdClass zurück — cache.serializable_classes prüfen.\n");
    exit(1);
}

echo "pubkey: {$pubkey}\n";
echo "cache:  nostr:profile:{$pubkey}\n";
echo 'event:  '.json_encode($readBack)."\n";
