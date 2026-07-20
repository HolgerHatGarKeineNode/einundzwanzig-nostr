/**
 * Raeumt beim Abmelden den Klartext-Cache des Chats vom Geraet.
 *
 * Das Chat-SDK legt die Nachrichten des Antragsraums (kind 9) in der IndexedDB
 * `einundzwanzig-cache-<pubkey>` ab — unverschluesselt, bis zu 300 pro Raum,
 * 30 Tage. Geleert wird die im Package nur von dessen eigenem `logout()`. Der
 * Verein meldet aber serverseitig per POST /logout ab und ruft das nie: Die
 * Nachrichten des Vorstands ueberlebten das Abmelden am Geraet, und jedes
 * kuenftige XSS irgendwo im Verein haette Zugriff darauf.
 *
 * Bewusst LEICHT und ohne Top-Level-Import: Der Knopf steht in jedem Layout.
 * Die meisten Nutzer oeffnen nie einen Chat — fuer die darf das Abmelden
 * NICHTS nachladen. Deshalb zwei Wege:
 *   1. Laeuft die Insel gerade (`window.__ezChatClearCache`, gesetzt beim
 *      Insel-Boot), raeumt deren clearCache auf. Nur die kann die offene
 *      IndexedDB-Verbindung vorher schliessen; ein blindes deleteDatabase
 *      liefe sonst in `blocked`.
 *   2. Sonst direkt ueber die IndexedDB-API. Das trifft den Normalfall: Cache
 *      aus einem frueheren Besuch, SDK auf dieser Seite nie geladen.
 *
 * Das Abmelden wartet hoechstens kurz darauf und geht dann in jedem Fall
 * weiter — es darf an dieser Hygiene nicht haengen bleiben.
 */
const DB_PREFIX = 'einundzwanzig-cache'

/** Schluessel der welshman-Session; sonst startete die Insel danach mit der alten Identitaet. */
const SESSION_KEYS = ['pubkey', 'sessions', 'activeSpaceUrl']

const WIPE_TIMEOUT_MS = 1500

export default function nostrLogout() {
    return {
        /**
         * Erst raeumen, dann absenden. `x-on:submit.prevent` haelt das Formular
         * genau so lange auf.
         */
        async submitAfterWipe(form) {
            try {
                await Promise.race([wipeChatCache(), delay(WIPE_TIMEOUT_MS)])
            } catch {
                // Aufraeumen ist Hygiene, kein Tuerwaechter — das Abmelden zaehlt.
            }
            form.submit()
        },
    }
}

async function wipeChatCache() {
    if (typeof window.__ezChatClearCache === 'function') {
        await window.__ezChatClearCache()
    }

    const names = new Set([DB_PREFIX])

    // Der Name traegt den pubkey (eine DB je Identitaet). Der steht in der
    // welshman-Session — ohne SDK die einzige Quelle.
    try {
        const pubkey = JSON.parse(localStorage.getItem('pubkey') ?? 'null')
        if (typeof pubkey === 'string' && pubkey) {
            names.add(`${DB_PREFIX}-${pubkey}`)
        }
    } catch {
        // Kaputter Slot — die Aufzaehlung unten faengt es ab.
    }

    // Ergaenzend aufzaehlen: faengt DBs frueherer Identitaeten am selben Geraet.
    // Nicht jeder Browser kann das (Firefox konnte es lange nicht), deshalb nur
    // als Zugabe zum berechneten Namen.
    if (typeof indexedDB.databases === 'function') {
        try {
            for (const db of await indexedDB.databases()) {
                if (String(db.name).startsWith(DB_PREFIX)) {
                    names.add(db.name)
                }
            }
        } catch {
            // Aufzaehlung verweigert — der berechnete Name bleibt.
        }
    }

    await Promise.all([...names].map(deleteDatabase))

    SESSION_KEYS.forEach((key) => localStorage.removeItem(key))
}

/** Loeschen, das nie haengt — auch nicht, wenn eine Verbindung offen blieb. */
function deleteDatabase(name) {
    return new Promise((resolve) => {
        try {
            const request = indexedDB.deleteDatabase(name)
            request.onsuccess = () => resolve()
            request.onerror = () => resolve()
            request.onblocked = () => resolve()
        } catch {
            resolve()
        }
    })
}

const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms))
