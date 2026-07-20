import { execFileSync } from 'node:child_process'
import { test, expect } from '@playwright/test'
import { getPublicKey } from 'nostr-tools/pure'
import { hexToBytes } from 'nostr-tools/utils'
import { installLocalNip07 } from './support/local-nip07.js'
import { expectColdStart } from './support/chat-island.js'

/**
 * Das aufklappbare Chat-Band auf der Antragsseite (show.blade.php).
 *
 * Vier Fragen, die nur ein echter Browser beantwortet:
 *   1. Traegt der Aufruf der Seite (angemeldet, Raum existiert, Chat NICHT
 *      angeklickt) irgendeinen Chat-Chunk nach — auf BEIDEN Viewports?
 *   2. Startet das Band auf dem Telefon zu, auf dem Desktop offen? Ist die
 *      Kopfzeile auf dem Telefon eine 44px-Trefferflaeche, die aufklappt?
 *   3. Uebersteht das Auf-/Zuklappen eine Livewire-Aktualisierung (Morph)?
 *      `wire:ignore.self` am Schalter, `style="display:none"` statt
 *      `x-cloak` am Panel — beide Entscheidungen zielen genau hierauf.
 *
 * Voraussetzung (siehe chat-feed-embed.spec.js fuer den vollen Aufbau):
 *   ./scripts/zooid-testserver.sh start
 *   DB_DATABASE=/tmp/e2e-verein.sqlite NOSTR_SPACE_URL=ws://localhost:3341/ \
 *     php artisan serve --port=8137
 */

const PROPOSAL_SLUG = process.env.E2E_PROPOSAL_SLUG ?? 'lightning-watchtower-fur-mitglieder'
/** Wegwerf-Vorstand, siehe chat-feed-board.spec.js. */
const BOARD_SEC = process.env.E2E_BOARD_SEC ?? 'a08bacac006cbee5ad0d8cc685e8f0cf375306345367c4793393c19d1ccdffb4'

const MOBILE = { width: 390, height: 844 }
const DESKTOP = { width: 1280, height: 800 }

/** Dieselbe Menge Fremdanfragen wie in chat-feed-board.spec.js Test 2. */
const CHAT_ASSET_PATTERN = /welshman|emojibase|\/assets\/js-|storage-|session-|groups-/

/** Die DB-KOPIE aus dem Bericht — niemals die Arbeitsdatenbank. */
const DB_PATH = process.env.E2E_DB_PATH ?? '/tmp/e2e-verein.sqlite'

test.describe.configure({ timeout: 120000 })

for (const [label, viewport] of Object.entries({ mobile: MOBILE, desktop: DESKTOP })) {
    test(`Punkt 1: kein Chat-Chunk beim blossen Aufruf (${label}, ${viewport.width}px)`, async ({ page }) => {
        const chatRequests = []
        let websockets = 0

        page.on('request', (r) => {
            if (CHAT_ASSET_PATTERN.test(r.url())) {
                chatRequests.push(r.url())
            }
        })
        page.on('websocket', (ws) => {
            websockets++
            chatRequests.push(`[websocket] ${ws.url()}`)
        })

        await page.setViewportSize(viewport)
        await installLocalNip07(page)

        await page.goto(`/association/project-support/${PROPOSAL_SLUG}`)
        await expectColdStart(page)
        // Unterhalb von lg steckt die Sidebar mit dem Login-Knopf off-canvas
        // (fixed, ausserhalb des Viewports) hinter dem Hamburger-Menue.
        if (label === 'mobile') {
            await page.getByRole('button', { name: 'Menü öffnen' }).click()
        }
        await page.getByRole('button', { name: /Mit Nostr verbinden/i }).first().click()
        await expect(page.getByText('Chat zum Antrag')).toBeVisible({ timeout: 60000 })

        // Zeit fuer traege Ladung: Ein Chunk, der beim Boot statt beim Klick
        // nachgezogen wird, waere spaetestens jetzt unterwegs.
        await page.waitForTimeout(3000)

        expect(chatRequests, `Chat-Assets ohne Klick geladen (${label}): ${chatRequests.join(' | ')}`).toEqual([])
        expect(websockets, `WebSocket ohne Klick geoeffnet (${label})`).toBe(0)
    })
}

test('Punkt 2: Band startet auf dem Telefon zu, auf dem Desktop offen', async ({ page }) => {
    await installLocalNip07(page)

    // ── Mobil: zu, Kopfzeile ist eine >=44px-Trefferflaeche, klappt auf ──────
    await page.setViewportSize(MOBILE)
    await page.goto(`/association/project-support/${PROPOSAL_SLUG}`)
    // Unterhalb von lg steckt die Sidebar mit dem Login-Knopf off-canvas
    // (fixed, ausserhalb des Viewports) hinter dem Hamburger-Menue.
    await page.getByRole('button', { name: 'Menü öffnen' }).click()
    await page.getByRole('button', { name: /Mit Nostr verbinden/i }).first().click()

    const header = page.getByRole('button', { name: 'Chat zum Antrag' })
    await expect(header).toBeVisible({ timeout: 60000 })

    await expect(header).toHaveAttribute('aria-expanded', 'false')
    const panel = page.locator('#chat-band-panel')
    await expect(panel).toBeHidden()

    const box = await header.boundingBox()
    expect(box, 'Kopfzeile hat keine Bounding-Box').not.toBeNull()
    expect(box.height, `Kopfzeile ist nur ${box.height}px hoch, verlangt sind >=44px`).toBeGreaterThanOrEqual(44)

    await page.screenshot({
        path: '/tmp/claude-1000/-home-user-Code-einundzwanzig-nostr/576545e5-100b-4a8e-a905-9e6c6ed70562/scratchpad/screenshots/chat-band-mobile-closed.png',
        fullPage: false,
    })

    await header.click()
    await expect(header).toHaveAttribute('aria-expanded', 'true')
    await expect(panel).toBeVisible()

    await page.screenshot({
        path: '/tmp/claude-1000/-home-user-Code-einundzwanzig-nostr/576545e5-100b-4a8e-a905-9e6c6ed70562/scratchpad/screenshots/chat-band-mobile-open.png',
        fullPage: false,
    })

    // ── Desktop: derselbe Login, neuer Viewport → soll offen starten ────────
    await page.setViewportSize(DESKTOP)
    await page.reload()
    await expect(page.getByRole('button', { name: 'Chat zum Antrag' })).toBeVisible({ timeout: 60000 })

    const desktopHeader = page.getByRole('button', { name: 'Chat zum Antrag' })
    await expect(desktopHeader).toHaveAttribute('aria-expanded', 'true')
    await expect(panel).toBeVisible()

    await page.screenshot({
        path: '/tmp/claude-1000/-home-user-Code-einundzwanzig-nostr/576545e5-100b-4a8e-a905-9e6c6ed70562/scratchpad/screenshots/chat-band-desktop-open.png',
        fullPage: false,
    })
})

test('Punkt 3: Band bleibt nach einer Livewire-Aktualisierung auf-/zuklappbar', async ({ page }) => {
    // Der Trigger fuer die Aktualisierung ist eine Stimmabgabe — die Komponente
    // bietet keine Rueckgaengig-Aktion. Auf der DB-KOPIE (nie der
    // Arbeitsdatenbank) wird die Stimme dieses Wegwerf-Vorstands darum vor
    // jedem Lauf entfernt, sonst zeigt die Seite ab dem zweiten Lauf nur noch
    // "Du hast zugestimmt" und der Test kann die Aktualisierung nicht ausloesen.
    const boardPubkey = getPublicKey(hexToBytes(BOARD_SEC))
    execFileSync('sqlite3', [DB_PATH, `
        DELETE FROM votes
        WHERE einundzwanzig_pleb_id IN (SELECT id FROM einundzwanzig_plebs WHERE pubkey = '${boardPubkey}')
          AND project_proposal_id IN (SELECT id FROM project_proposals WHERE slug = '${PROPOSAL_SLUG}');
    `])

    await page.setViewportSize(DESKTOP)
    await installLocalNip07(page, BOARD_SEC)

    await page.goto(`/association/project-support/${PROPOSAL_SLUG}`)
    await page.getByRole('button', { name: /Mit Nostr verbinden/i }).first().click()

    const header = page.getByRole('button', { name: 'Chat zum Antrag' })
    const panel = page.locator('#chat-band-panel')
    await expect(header).toBeVisible({ timeout: 60000 })

    // Vorbedingung: Desktop startet offen.
    await expect(header).toHaveAttribute('aria-expanded', 'true')
    await expect(panel).toBeVisible()

    // Vorbedingung fuer die Stimmabgabe: Dieses Vorstandsmitglied darf noch
    // abstimmen. Sonst zeigt die Seitenspalte "Du hast zugestimmt" statt des
    // Knopfes, und der Test triggert keine Aktualisierung.
    const voteTrigger = page.getByRole('button', { name: 'Zustimmen' }).first()
    await expect(voteTrigger, 'Kein Zustimmen-Knopf sichtbar — hat dieses Testkonto schon abgestimmt?')
        .toBeVisible({ timeout: 15000 })

    // ── Die Livewire-Aktualisierung ausloesen (Stimmabgabe) ──────────────────
    await voteTrigger.click()
    const dialog = page.locator('dialog[open]')
    await expect(dialog).toBeVisible({ timeout: 10000 })
    await dialog.getByRole('button', { name: 'Zustimmen' }).click()
    await expect(page.getByText('Deine Stimme wurde gezählt.')).toBeVisible({ timeout: 15000 })
    await expect(dialog).toBeHidden()

    // ── Beweis, dass der Morph den offenen Zustand NICHT zurueckgesetzt hat ──
    // Genau das ist die im Auftrag genannte Regressionsgefahr: Ohne
    // wire:ignore.self / display:none stuende hier "false", obwohl das Panel
    // sichtbar offen dasteht.
    await expect(header).toHaveAttribute('aria-expanded', 'true')
    await expect(panel).toBeVisible()

    // ── Und es laesst sich danach noch normal zu- und aufklappen ────────────
    await header.click()
    await expect(header).toHaveAttribute('aria-expanded', 'false')
    await expect(panel).toBeHidden()

    await header.click()
    await expect(header).toHaveAttribute('aria-expanded', 'true')
    await expect(panel).toBeVisible()
})
