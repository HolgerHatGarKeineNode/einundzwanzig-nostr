import { test, expect } from '@playwright/test'
import { installLocalNip07 } from './support/local-nip07.js'
import { expectColdStart } from './support/chat-island.js'

/**
 * Der Profil-Seed der eingebetteten Raum-Ansicht — was nur ein Netzwerk-
 * Mitschnitt belegen kann:
 *
 *   P1  Die Insel ruft `GET /nostr/profiles?pubkeys=…` tatsaechlich auf, und der
 *       Aufruf endet mit 200 (vorher: 404, `seedChunk` verschluckte ihn still).
 *   P2  Der Aufruf geht an UNSERE Domain, nicht an einen fremden Host — der
 *       Relay-Riegel bleibt also unangetastet. Gemessen wie in
 *       chat-feed-board.spec.js: Zuwachs an Fremdhosts, sobald die Insel bootet.
 *   P3  Aus der Antwort wird im Verlauf ein NAME statt eines npub-Kuerzels.
 *
 * Voraussetzung wie bei chat-feed-board.spec.js (Relay 3341, DB-Kopie, Server
 * auf 8137). ZUSAETZLICH: Der Server-Cache muss ein kind-0 des Antragstellers
 * kennen. Die Wegwerf-Schluessel dieses Aufbaus haben naturgemaess kein Profil
 * auf einem oeffentlichen Indexer, deshalb wird es lokal signiert und NUR in
 * unseren eigenen Cache gelegt (nirgends publiziert) — siehe
 * tests/e2e/support/seed-submitter-profile.php.
 *
 * Genau das macht P3 aussagekraeftig: Das Event liegt AUSSCHLIESSLICH im
 * Server-Cache und auf keinem Relay. Der Name kann also nur ueber den neuen
 * Endpunkt in den Browser gekommen sein, nicht ueber die Live-Aufloesung.
 */

const PROPOSAL_SLUG = process.env.E2E_PROPOSAL_SLUG ?? 'lightning-watchtower-fur-mitglieder'
const BOARD_SEC = process.env.E2E_BOARD_SEC ?? 'a08bacac006cbee5ad0d8cc685e8f0cf375306345367c4793393c19d1ccdffb4'

/** Wegwerf-Antragsteller (Pubkey zu SUBMITTER_SEC), dessen Profil geseedet ist. */
const SUBMITTER_PUBKEY = '424f10e956aee6f5c3a92206f59077d576e7e5f628c1b06e8da1b229018c47f6'
const SEEDED_NAME = 'E2E Antragsteller'

const APP_HOST = '127.0.0.1:8137'
const RELAY_HOST = 'localhost:3341'

test.describe.configure({ timeout: 180000 })

test('Profil-Seed: eigener Endpunkt antwortet, Name erscheint, kein Fremdhost', async ({ page }) => {
    const seedCalls = []
    const foreignHosts = new Set()
    const failures = []

    page.on('pageerror', (e) => failures.push(String(e)))

    const noteHost = (rawUrl) => {
        try {
            const url = new URL(rawUrl)
            if (url.host !== APP_HOST && url.host !== RELAY_HOST) {
                foreignHosts.add(`${url.protocol}//${url.host}`)
            }
        } catch {
            // data:/blob: — kein Netzverkehr.
        }
    }
    page.on('request', (r) => noteHost(r.url()))
    page.on('websocket', (ws) => noteHost(ws.url()))

    // Der Beleg fuer P1/P2: Adresse UND Status jedes Seed-Aufrufs.
    page.on('response', async (res) => {
        if (!res.url().includes('/nostr/profiles')) {
            return
        }
        const url = new URL(res.url())
        seedCalls.push({ host: url.host, status: res.status(), query: url.search })
    })

    await installLocalNip07(page, BOARD_SEC)

    try {
        await page.goto(`/association/project-support/${PROPOSAL_SLUG}`)
        await expectColdStart(page)
        await page.getByRole('button', { name: /Mit Nostr verbinden/i }).first().click()
        await expect(page.getByText('Chat zum Antrag')).toBeVisible({ timeout: 60000 })

        await page.waitForTimeout(2000)
        const baseline = [...foreignHosts]

        const loadButton = page.getByRole('button', { name: /Chat hier laden/i })
        if (await loadButton.isVisible()) {
            await loadButton.click()
        }

        await expect(page.getByLabel('Nachricht schreiben')).toBeVisible({ timeout: 60000 })
        await expect(page.locator('.chat-content').first()).toBeVisible({ timeout: 30000 })

        // ── P1: der Endpunkt wird aufgerufen und antwortet ───────────────────
        await expect
            .poll(() => seedCalls.length, { timeout: 30000, message: 'Die Insel hat /nostr/profiles nie aufgerufen' })
            .toBeGreaterThan(0)
        expect(seedCalls.every((c) => c.status === 200), `Seed-Aufrufe: ${JSON.stringify(seedCalls)}`).toBe(true)
        expect(seedCalls.some((c) => c.query.includes(SUBMITTER_PUBKEY)),
            `Antragsteller nicht angefragt: ${JSON.stringify(seedCalls)}`).toBe(true)

        // ── P2: an unsere eigene Domain, und kein neuer Fremdhost ────────────
        expect(seedCalls.every((c) => c.host === APP_HOST), `Fremder Host im Seed: ${JSON.stringify(seedCalls)}`).toBe(true)
        await page.waitForTimeout(4000)
        const added = [...foreignHosts].filter((host) => !baseline.includes(host))
        expect(added, `Die Insel hat Verbindungen an Fremdhosts hinzugefuegt: ${added.join(' | ')}`).toEqual([])

        // ── P3: aus dem Seed wird ein Name im Verlauf ────────────────────────
        await expect(page.getByText(SEEDED_NAME).first()).toBeVisible({ timeout: 30000 })

        // ── P4: auch ein ECHTES, oeffentlich auffindbares Profil kommt durch ──
        // Die Wegwerf-Schluessel oben liegen auf keinem Indexer; dieser Griff
        // belegt den anderen Fall: ein Pubkey mit kind-0 auf purplepag.es. Der
        // Browser fragt dabei ausschliesslich unsere eigene Domain (relativer
        // Pfad, gleiche Session) — den Indexer kontaktiert der Server.
        const real = await page.evaluate(async () => {
            const res = await fetch('/nostr/profiles?pubkeys=3bf0c63fcb93463407af97a5e5ee64fa883d107ef9e558472c4eb9aaaefa459d', {
                headers: { Accept: 'application/json' },
            })
            const body = await res.json()

            return { status: res.status, event: body.events?.[0] ?? null }
        })
        console.log('Echtes Profil ueber den Endpunkt:', JSON.stringify(real).slice(0, 300))
        expect(real.status).toBe(200)
        expect(real.event, 'Kein Event fuer einen oeffentlich auffindbaren Pubkey').not.toBeNull()
        // Vollstaendiges, signiertes Event — der Client prueft die Signatur selbst.
        expect(Object.keys(real.event).sort()).toEqual(
            ['content', 'created_at', 'id', 'kind', 'pubkey', 'sig', 'tags'],
        )
        expect(JSON.parse(real.event.content).name).toBeTruthy()

        expect(failures, `JS-Fehler auf der Seite: ${failures.join(' | ')}`).toEqual([])
    } finally {
        console.log('Seed-Aufrufe:', JSON.stringify(
            seedCalls.map((c) => ({ ...c, query: c.query.slice(0, 90) + (c.query.length > 90 ? '…' : '') })),
        ))
        console.log('Fremdhosts:', JSON.stringify([...foreignHosts]))
    }
})
