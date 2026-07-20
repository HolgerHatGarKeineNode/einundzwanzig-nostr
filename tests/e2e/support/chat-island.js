/**
 * Warten auf ein ZUGESTELLTES Senden — ohne Schlafen.
 *
 * `send()` im Package (bridge.ts) leert den Entwurf, setzt `sending = true` und
 * haelt das, bis `sendRoomMessage()` aufgeloest ist: also bis der Relay das
 * Event bestaetigt oder abgelehnt hat. Bei Ablehnung stellt es Entwurf und
 * Fehlertext wieder her.
 *
 * Daraus ergibt sich ein eindeutiges Erfolgskriterium aus dem Produkt selbst:
 *   Entwurf leer  UND  nicht mehr sendend  UND  kein Fehlertext.
 * Vor dem Klick ist es unerfuellt (der Entwurf traegt den Text), waehrend des
 * Sendens auch (`sending`), und bei Fehlschlag ebenfalls (Entwurf zurueck).
 *
 * Erst danach darf ein Test neu laden. Sonst reisst der Neuaufbau die
 * WebSocket-Verbindung ab, bevor das Event auf der Leitung war — die Nachricht
 * stand dann zwar optimistisch im Verlauf, kam aber nie am Relay an.
 */
/**
 * Belegt, dass dieser Lauf ein ERSTLOGIN ist: keine welshman-Session, kein
 * Chat-Cache. Genau dort lag der Reihenfolge-Fehler — ein Lauf auf
 * vorgewaermtem Zustand beweist nichts. Playwright gibt jedem Test einen
 * frischen Kontext; diese Pruefung macht das nachweisbar, statt es anzunehmen.
 */
export async function expectColdStart(page) {
    const state = await page.evaluate(async () => ({
        pubkey: localStorage.getItem('pubkey'),
        sessions: localStorage.getItem('sessions'),
        caches: (await indexedDB.databases()).map((d) => d.name).filter((n) => String(n).startsWith('einundzwanzig-cache')),
    }))

    if (state.pubkey || state.sessions || state.caches.length) {
        throw new Error(`Kein Kaltstart: ${JSON.stringify(state)}`)
    }
}

export async function waitForMessageDelivered(page, timeout = 30000) {
    await page.waitForFunction(
        () => {
            const el = document.querySelector('[x-data^="projectChatRoomFeed"]')
            if (!el) {
                return false
            }
            const data = window.Alpine.$data(el)

            return data.draft === '' && data.sending === false && data.sendError === ''
        },
        undefined,
        { timeout },
    )
}
