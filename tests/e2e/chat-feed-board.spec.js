import { test, expect } from '@playwright/test'
import { installLocalNip07 } from './support/local-nip07.js'
import { expectColdStart, waitForMessageDelivered } from './support/chat-island.js'

/**
 * Der Vorstands-Pfad der eingebetteten Raum-Ansicht — und die drei Zusagen, die
 * nur ein Netzwerk-Mitschnitt belegen kann:
 *
 *   B1  Kein toter Knopf: Aktionen, deren Overlay der Ausschnitt nicht rendert
 *       (Admin-„Nachricht entfernen"), landen im vollen Client. Gegenprobe: Das
 *       Loeschen der EIGENEN Nachricht oeffnet das Modal hier, ohne neuen Tab.
 *   B2  Kein Datenabfluss: Beim Oeffnen der Antragsseite geht keine Verbindung
 *       an einen anderen Host als den Space-Relay.
 *   B3  Kein Rueckstand: Nach dem Abmelden existiert keine
 *       `einundzwanzig-cache-*`-Datenbank mehr.
 *
 * Voraussetzung (bewusst nicht automatisch, damit kein Lauf die
 * Arbeitsdatenbank oder den Prod-Relay trifft):
 *   ./scripts/zooid-testserver.sh start
 *   # Wegwerf-Vorstand: Relay-Rolle mit can_manage + allowpubkey, und im Raum
 *   # p4e07408562be per kind 9000 aufgenommen (siehe Bericht).
 *   cp database/database.sqlite /tmp/e2e-verein.sqlite
 *   # In der KOPIE: pleb des ersten Vorstands-npub auf den Wegwerf-Pubkey,
 *   # Antragsteller auf den Wegwerf-Antragsteller, Raum-ID am Antrag setzen.
 *   DB_DATABASE=/tmp/e2e-verein.sqlite NOSTR_SPACE_URL=ws://localhost:3341/ \
 *     php artisan serve --port=8137 --no-reload
 */

const PROPOSAL_SLUG = process.env.E2E_PROPOSAL_SLUG ?? 'lightning-watchtower-fur-mitglieder'

/** Wegwerf-Vorstand, ausschliesslich lokal (Relay-Rolle in der Test-TOML). */
const BOARD_SEC = process.env.E2E_BOARD_SEC ?? 'a08bacac006cbee5ad0d8cc685e8f0cf375306345367c4793393c19d1ccdffb4'

/** Der einzige Host, mit dem die Insel sprechen darf — plus die App selbst. */
const APP_HOST = '127.0.0.1:8137'
const RELAY_HOST = 'localhost:3341'

test.describe.configure({ timeout: 180000 })

test('Vorstand: kein toter Knopf, kein Fremdhost, kein Cache-Rueckstand', async ({ page, context }) => {
    const failures = []
    const logs = []
    const foreignHosts = new Set()

    page.on('pageerror', (e) => failures.push(String(e)))
    page.on('console', (m) => logs.push(`[${m.type()}] ${m.text().slice(0, 160)}`))

    /**
     * Die Seite selbst spricht mit Fremdhosts, unabhaengig vom Chat: das
     * FontAwesome-Kit, die Schrift (absolut gegen APP_URL) und die
     * Profilbilder der Mitglieder aus der VEREINS-Datenbank (m.primal.net).
     * Gemessen wird deshalb der ZUWACHS: Welche Hosts kommen dazu, sobald die
     * Insel bootet? Das ist genau die Frage von B2 — und nur der Zuwachs ist
     * dem Chat zuzurechnen.
     */
    const noteHost = (rawUrl) => {
        try {
            const url = new URL(rawUrl)
            if (url.host !== APP_HOST && url.host !== RELAY_HOST) {
                foreignHosts.add(`${url.protocol}//${url.host}`)
            }
        } catch {
            // Kein absoluter URL (data:, blob:) — kein Netzverkehr.
        }
    }
    page.on('request', (r) => noteHost(r.url()))

    // Kontext-weit, also inklusive neuer Tabs: Welche Adresse wurde ANGEFRAGT?
    // Der volle Client laeuft im Testaufbau nicht (der Space ist hier ein
    // nackter Relay ohne HTTP-Oberflaeche), die Navigation scheitert also — die
    // Absicht steht trotzdem im Request.
    const requested = []
    context.on('request', (r) => requested.push(r.url()))
    page.on('websocket', (ws) => {
        logs.push(`[websocket] ${ws.url()}`)
        noteHost(ws.url())
    })

    await installLocalNip07(page, BOARD_SEC)

    try {
        await page.goto(`/association/project-support/${PROPOSAL_SLUG}`)
        await expectColdStart(page)
        await page.getByRole('button', { name: /Mit Nostr verbinden/i }).first().click()
        await expect(page.getByText('Chat zum Antrag')).toBeVisible({ timeout: 60000 })

        // Ausgangsstand OHNE Insel: Die Seite ist fertig, der Chat noch nicht
        // geladen — alles hier ist Verkehr der Vereinsseite.
        await page.waitForTimeout(2000)
        const baseline = [...foreignHosts]
        console.log('Fremdhosts der Seite OHNE Insel:', JSON.stringify(baseline))

        const loadButton = page.getByRole('button', { name: /Chat hier laden/i })
        if (await loadButton.isVisible()) {
            await loadButton.click()
        }

        const composer = page.getByLabel('Nachricht schreiben')
        await expect(composer).toBeVisible({ timeout: 60000 })

        // Der Vorstand liest die Nachrichten des Antragstellers.
        const foreignMessage = page.locator('.chat-content').first()
        await expect(foreignMessage).toBeVisible({ timeout: 30000 })

        // ── B2: Die Insel darf KEINEN neuen Host hinzufuegen ─────────────────
        // Der Raum ist geladen, die Zap-Abfrage aus loadRoomZaps ist gelaufen —
        // ohne den Relay-Riegel stuenden hier relay.primal.net, nos.lol,
        // theforest.nostr1.com und nostr.oxtr.dev.
        await page.waitForTimeout(4000)
        const added = [...foreignHosts].filter((host) => !baseline.includes(host))
        expect(added, `Die Insel hat Verbindungen an Fremdhosts hinzugefuegt: ${added.join(' | ')}`).toEqual([])

        // ── B1: Admin-Aktion auf eine FREMDE Nachricht ───────────────────────
        // „Nachricht entfernen" oeffnet im Package das Modal
        // `admin-delete-message`, das der Ausschnitt nicht rendert. Erwartet:
        // neuer Tab in den vollen Client statt Klick ins Leere.
        // Der Verlauf ist `flex-col-reverse`: neueste Nachricht zuerst im DOM.
        // Die AELTESTE (`.last()`) stammt immer vom Antragsteller — eigene
        // Nachrichten dieses Laufs sind naturgemaess juenger. Das haelt auch bei
        // wiederholten Laeufen, die den Raum weiter fuellen.
        const foreignRow = page.locator('[id^="msg-"]').last()
        await expect(foreignRow.getByRole('button', { name: 'Nachricht löschen' })).toHaveCount(0)
        await foreignRow.hover()
        await foreignRow.getByRole('button', { name: 'Weitere Aktionen' }).click()
        const adminDelete = page.getByRole('menuitem', { name: 'Nachricht entfernen' })
        await expect(adminDelete).toBeVisible({ timeout: 10000 })

        const popupPromise = context.waitForEvent('page', { timeout: 15000 })
        await adminDelete.click()
        const popup = await popupPromise
        expect(requested.some((url) => url.includes('/rooms/p4e07408562be')),
            `Kein Aufruf des vollen Clients: ${requested.slice(-5).join(' | ')}`).toBe(true)
        await popup.close()

        // ── B1 Gegenprobe: eigene Nachricht → Modal HIER, kein neuer Tab ─────
        const text = `E2E Vorstand ${Date.now()}`
        await composer.fill(text)
        await page.getByRole('button', { name: 'Senden', exact: true }).click()
        await expect(page.getByText(text)).toBeVisible({ timeout: 30000 })
        await expect(page.getByText(/Erneut senden/)).toBeHidden()
        await waitForMessageDelivered(page)

        // Der Verlauf ist `flex-col-reverse`: Die NEUESTE Nachricht steht als
        // erste im DOM. `.first()` ist also die eben gesendete.
        const ownRow = page.locator('[id^="msg-"]').first()
        await ownRow.hover()
        await ownRow.getByRole('button', { name: 'Nachricht löschen' }).click()
        await expect(page.getByText('Das lässt sich nicht rückgängig machen.')).toBeVisible({ timeout: 10000 })
        expect(context.pages()).toHaveLength(1)
        await page.getByRole('button', { name: 'Abbrechen' }).click()

        // ── B3: Abmelden raeumt den Klartext-Cache ───────────────────────────
        const before = await page.evaluate(async () => (await indexedDB.databases()).map((d) => d.name))
        expect(before.filter((n) => String(n).startsWith('einundzwanzig-cache')).length,
            'Vor dem Abmelden muss ein Chat-Cache existieren, sonst prueft der Test nichts').toBeGreaterThan(0)

        await page.getByRole('button', { name: 'Logout' }).click()
        await page.waitForURL(/association\/profile/, { timeout: 30000 })

        const after = await page.evaluate(async () => (await indexedDB.databases()).map((d) => d.name))
        expect(after.filter((n) => String(n).startsWith('einundzwanzig-cache')),
            `Cache ueberlebt das Abmelden: ${after.join(' | ')}`).toEqual([])

        expect(failures, `JS-Fehler auf der Seite: ${failures.join(' | ')}`).toEqual([])
    } finally {
        console.log('--- Browser (Vorstand) ---\n' + logs.slice(-30).join('\n'))
        console.log('Fremdhosts:', JSON.stringify([...foreignHosts]))
    }
})

test('Abmelden auf einer Seite ohne Chat laedt keinen Insel-Chunk', async ({ page }) => {
    const islandRequests = []
    page.on('request', (r) => {
        if (/welshman|emojibase|\/assets\/js-|storage-|session-|groups-/.test(r.url())) {
            islandRequests.push(r.url())
        }
    })

    await installLocalNip07(page, BOARD_SEC)

    await page.goto('/association/profile')
    await page.getByRole('button', { name: /Mit Nostr verbinden/i }).first().click()
    await expect(page.getByRole('button', { name: 'Logout' })).toBeVisible({ timeout: 60000 })

    await page.getByRole('button', { name: 'Logout' }).click()
    await page.waitForURL(/association\/profile/, { timeout: 30000 })

    expect(islandRequests, `Insel-Chunks beim Abmelden geladen: ${islandRequests.join(' | ')}`).toEqual([])
})
