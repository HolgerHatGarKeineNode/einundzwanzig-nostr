import { test, expect } from '@playwright/test'
import { installLocalNip07 } from './support/local-nip07.js'
import { expectColdStart, waitForMessageDelivered } from './support/chat-island.js'

/**
 * Die eingebettete Raum-Ansicht auf der Detailseite einer Projektunterstuetzung.
 *
 * Zwei Fragen, die nur ein echter Browser beantwortet:
 *   1. Traegt die spaete Registrierung? Die Package-Komponenten kommen erst NACH
 *      Alpines Start dazu; das Chat-Markup steckt darum in `<template x-if>`.
 *   2. Bleibt eine Seite OHNE Chat unberuehrt — kein SDK, kein IndexedDB-Cache,
 *      keine Relay-Verbindung?
 *
 * Voraussetzung, bewusst nicht automatisch gestartet, damit kein Lauf die
 * Arbeitsdatenbank oder den Prod-Relay trifft:
 *   ./scripts/zooid-testserver.sh start
 *   cp database/database.sqlite /tmp/e2e-verein.sqlite
 *   sqlite3 /tmp/e2e-verein.sqlite \
 *     "UPDATE einundzwanzig_plebs SET pubkey='424f10…', npub='npub1gf83…' WHERE id=7;
 *      UPDATE project_proposals SET nostr_group_h='p4e07408562be' WHERE id=3;"
 *   DB_DATABASE=/tmp/e2e-verein.sqlite NOSTR_SPACE_URL=ws://localhost:3341/ \
 *     php artisan serve --port=8137
 *
 * Der Antragsteller-Schluessel ist im Raum Mitglied (kind 9000) und am Relay
 * zugelassen (allowpubkey) — beides hat scripts/zooid-testserver.sh gesetzt.
 */

const PROPOSAL_SLUG = process.env.E2E_PROPOSAL_SLUG ?? 'lightning-watchtower-fur-mitglieder'

test.describe.configure({ timeout: 120000 })

test('Mitglied liest und schreibt im eingebetteten Antragsraum', async ({ page }) => {
    const failures = []
    const logs = []
    page.on('pageerror', (e) => failures.push(String(e)))
    page.on('console', (m) => logs.push(`[${m.type()}] ${m.text().slice(0, 200)}`))
    page.on('requestfailed', (r) => logs.push(`[requestfailed] ${r.url().slice(0, 120)}`))

    await installLocalNip07(page)

    try {
        await page.goto(`/association/project-support/${PROPOSAL_SLUG}`)
        await expectColdStart(page)
        await page.getByRole('button', { name: /Mit Nostr verbinden/i }).first().click()

        // Der Login endet mit einem vollstaendigen Reload.
        await expect(page.getByText('Chat zum Antrag')).toBeVisible({ timeout: 60000 })

        // Erster Besuch: noch keine welshman-Session → die Insel laedt auf Klick.
        const loadButton = page.getByRole('button', { name: /Chat hier laden/i })
        if (await loadButton.isVisible()) {
            await loadButton.click()
        }

        // Beweist Punkt 1: Die erst jetzt registrierte Komponente greift im
        // nachgeschobenen Teilbaum — sonst bliebe das x-data wirkungslos.
        const composer = page.getByLabel('Nachricht schreiben')
        await expect(composer).toBeVisible({ timeout: 60000 })

        const text = `E2E ${Date.now()}`
        await composer.fill(text)
        await page.getByRole('button', { name: 'Senden', exact: true }).click()

        // Die Nachricht erscheint zuerst optimistisch — das allein beweist NICHTS
        // ueber den Relay. Erst der Neuaufbau der Seite zeigt sie aus dem Verlauf:
        // Danach kann sie nur vom Relay kommen.
        await expect(page.getByText(text)).toBeVisible({ timeout: 30000 })
        // Die Fehlerzeile steht immer im DOM (x-show), darf aber nicht sichtbar sein.
        await expect(page.getByText(/Erneut senden/)).toBeHidden()
        // Zustellung abwarten, BEVOR neu geladen wird — sonst misst der Test das
        // Abreissen der Verbindung statt der Persistenz (siehe support/chat-island.js).
        await waitForMessageDelivered(page)

        await page.reload()
        // Zweiter Besuch: Die welshman-Session steht im localStorage, die Insel
        // laedt ohne Zutun.
        await expect(page.getByText(text)).toBeVisible({ timeout: 60000 })

        expect(failures, `JS-Fehler auf der Seite: ${failures.join(' | ')}`).toEqual([])
    } finally {
        console.log('--- Browser (Chat) ---\n' + logs.slice(-30).join('\n'))
    }
})

test('Seite ohne Chat bootet das SDK nicht', async ({ page }) => {
    const failures = []
    const logs = []
    const chatRequests = []
    const failedRequests = []
    let websockets = 0

    /**
     * Zwei fehlschlagende Anfragen haben NICHTS mit dem Chat zu tun und wuerden
     * die Aussage nur verwaessern:
     *   - die Schriftart, die die App absolut gegen APP_URL (localhost:8000)
     *     verlinkt — der Test laeuft auf 8137,
     *   - window.nostr.min.js von jsDelivr, das dieser Test selbst abbricht,
     *     damit es die eingehaengte NIP-07-Bruecke nicht ueberschreibt.
     */
    const unrelatedFailure = /localhost:8000\/storage\/fonts\/|window\.nostr.*\.js/

    page.on('pageerror', (e) => failures.push(String(e)))
    page.on('console', (m) => logs.push(`[${m.type()}] ${m.text().slice(0, 200)}`))
    page.on('requestfailed', (r) => {
        if (!unrelatedFailure.test(r.url())) {
            failedRequests.push(r.url())
        }
    })
    page.on('websocket', (ws) => {
        websockets++
        logs.push(`[websocket] ${ws.url()}`)
    })
    page.on('request', (r) => {
        // Chunks der Insel, Bild-Proxy und Profil-Seed — nichts davon darf hier fallen.
        if (/\/img\/(avatar|msg|full)|\/nostr\/profiles|welshman|emojibase|compact/.test(r.url())) {
            chatRequests.push(r.url())
        }
    })

    await installLocalNip07(page)

    try {
        await page.goto('/association/profile')
        await page.getByRole('button', { name: /Mit Nostr verbinden/i }).first().click()
        await expect(page.getByRole('heading', { level: 1 }).first()).toBeVisible({ timeout: 60000 })
        await page.waitForTimeout(3000)

        const idbNames = await page.evaluate(async () => {
            const dbs = await indexedDB.databases()
            return dbs.map((d) => d.name)
        })

        expect(chatRequests, `Chat-Assets geladen: ${chatRequests.join(' | ')}`).toEqual([])
        expect(failedRequests, `Fehlgeschlagene Anfragen: ${failedRequests.join(' | ')}`).toEqual([])
        expect(idbNames.filter((n) => String(n).startsWith('einundzwanzig-cache'))).toEqual([])
        expect(websockets, 'Es wurde eine WebSocket-Verbindung geoeffnet').toBe(0)
        expect(failures, `JS-Fehler auf der Seite: ${failures.join(' | ')}`).toEqual([])
    } finally {
        console.log('--- Browser (ohne Chat) ---\n' + logs.slice(-30).join('\n'))
    }
})
