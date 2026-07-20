<?php

namespace App\Http\Controllers\Nostr;

use App\Http\Controllers\Controller;
use Einundzwanzig\Group\Nostr\ProfileCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Liefert rohe, signierte kind-0-Events (NIP-01) für den Profil-Seed der Chat-Insel.
 *
 * Gegenstück zu `seedChunk()` in `js/profiles.ts` des Packages: Der Browser ist auf
 * der Antragsseite per Relay-Riegel (`window.__nostrRelays`) auf den Space-Relay
 * beschränkt, damit keine Event-IDs eines privaten NIP-29-Raums an fremde Relays
 * abfließen. Profile liegen dort aber meist nicht — dieser Endpunkt löst sie
 * serverseitig über den Indexer auf und gibt sie unverändert weiter.
 *
 * Unverändert ist wörtlich gemeint: Der Client prüft jede Signatur selbst
 * (`verifyEvent`) und verwirft, was nicht passt. Abgeleitete Felder aus unserer
 * `profiles`-Tabelle wären dort wertlos, weil die Signatur beim Import verloren geht.
 *
 * Bewusst NICHT unter `Api\Nostr` wie `GetProfile` (Singular, anderer Zweck): Der Pfad
 * ist im Package fest verdrahtet (`/nostr/profiles`, kein `/api`-Präfix, relativ zur
 * eigenen Domain).
 */
class GetProfiles extends Controller
{
    /** Client-seitige Blockgröße in `js/profiles.ts` — größere Blöcke stammen nicht von uns. */
    private const MAX_PUBKEYS = 100;

    public function __invoke(Request $request, ProfileCache $profiles): JsonResponse
    {
        // KEIN Anmelde-Gate, bewusst. Ausgeliefert werden kind-0-Events — auf Nostr
        // vollständig öffentlich, jeder holt sie mit drei Zeilen direkt beim Relay.
        // Sie hinter eine Anmeldung zu stellen verheimlicht nichts und kostet etwas:
        // Läuft die Sitzung ab, schluckt `seedChunk` den fehlgeschlagenen Abruf
        // wortlos, und im Chat verschwinden still die Namen.
        //
        // Die reale Sorge ist eine andere — der Endpunkt lässt unseren Server
        // Relay-Abfragen ausführen. Dagegen wirkt die Drosselung (siehe Route), nicht
        // eine Anmeldung. Die Ziel-Relays sind fest verdrahtet (ProfileCache), der
        // Aufrufer bestimmt sie nicht: kein SSRF, nur Rechenzeit.
        $raw = array_filter(array_map('trim', explode(',', (string) $request->query('pubkeys', ''))));

        // Zu groß: sauber ablehnen statt kappen. Unsere Insel schickt nie mehr als
        // MAX_PUBKEYS; ein größerer Block kommt also von einem fremden Aufrufer, und
        // stilles Kappen würde ihm die Hälfte der Arbeit trotzdem abnehmen und den
        // Vertragsbruch verstecken.
        if (count($raw) > self::MAX_PUBKEYS) {
            return response()->json([
                'message' => 'Too many pubkeys, maximum is '.self::MAX_PUBKEYS.'.',
            ], 422);
        }

        // Ungültige Einträge still verwerfen: Ein einziger Tippfehler in der
        // Mitgliederliste darf nicht den ganzen Raum namenlos machen.
        $pubkeys = array_values(array_filter(
            $raw,
            static fn (string $pk): bool => preg_match('/^[0-9a-f]{64}$/', $pk) === 1,
        ));

        if ($pubkeys === []) {
            return response()->json(['events' => []]);
        }

        return response()->json(['events' => $profiles->get($pubkeys)]);
    }
}
