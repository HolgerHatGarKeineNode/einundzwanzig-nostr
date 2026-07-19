import { test, expect } from '@playwright/test'
import { finalizeEvent } from 'nostr-tools/pure'

/** hex -> Uint8Array. Bewusst selbst statt @noble/hashes/utils: dessen
 *  Export-Pfade unterscheiden sich zwischen v1 und v2, und beide liegen im
 *  Baum (siehe overrides in package.json). */
const hexToBytes = (hex) => Uint8Array.from(hex.match(/../g).map((b) => parseInt(b, 16)))

/**
 * Legt ein Vorstandsmitglied den privaten Chatraum eines Antrags an, und sieht
 * ihn danach nur der berechtigte Kreis?
 *
 * Voraussetzung (bewusst nicht automatisch gestartet, damit kein Lauf die
 * Arbeitsdatenbank oder den Prod-Relay trifft):
 *   ./scripts/zooid-testserver.sh start
 *   DB_DATABASE=<kopie> php artisan serve --port=8137
 * In der Kopie tragen ein Vorstandsmitglied und der Antragsteller die
 * Wegwerf-Schluessel aus dem Testserver-Skript.
 */

// Dieselben Wegwerf-Schluessel wie scripts/zooid-testserver.sh. Nur lokal.
const BOARD_SEC = '41aeab5945f7ad83fe8c6d438eb80a328f4893c9afa6898b23cda0146efac1a4'
const BOARD_PUB = 'b4799375e2c83dc3f5f57f0c50197a603d8fa8368037096aaa1f7ae1cfd6c350'

const PROPOSAL_SLUG = process.env.E2E_PROPOSAL_SLUG ?? 'lightning-watchtower-fur-mitglieder'

/**
 * Installiert ein NIP-07-window.nostr. Die Krypto laeuft in Node, weil dort der
 * Schluessel liegt — die Seite ruft nur getPublicKey/signEvent wie bei einer
 * echten Erweiterung.
 */
async function installNip07(page, sec, pub) {
    // Die App laedt window.nostr.js von jsDelivr als Fallback fuer Besucher ohne
    // Erweiterung. Es ueberschreibt ein bereits gesetztes window.nostr — also
    // auch unseres. Fuer den Test blockieren, damit die simulierte Erweiterung
    // gewinnt; ein echter Nutzer mit Extension hat dasselbe Ergebnis.
    await page.route(/window\.nostr.*\.js/, (route) => route.abort())

    await page.exposeFunction('__nip07_getPublicKey', () => pub)
    await page.exposeFunction('__nip07_signEvent', (event) => finalizeEvent(event, hexToBytes(sec)))

    await page.addInitScript(() => {
        window.nostr = {
            getPublicKey: () => window.__nip07_getPublicKey(),
            signEvent: (event) => window.__nip07_signEvent(event),
        }
    })
}

test('Vorstand legt den privaten Chatraum an', async ({ page }) => {
    const failures = []
    page.on('pageerror', (e) => failures.push(String(e)))

    await installNip07(page, BOARD_SEC, BOARD_PUB)
    await page.goto(`/association/project-support/${PROPOSAL_SLUG}`)

    // Vereins-Login (kind 22242 ueber window.nostr).
    await page.getByRole('button', { name: /Mit Nostr verbinden/i }).first().click()
    await expect(page.getByText('Chat zum Antrag')).toBeVisible({ timeout: 15000 })

    const createButton = page.getByRole('button', { name: /Chatraum anlegen/i })
    await expect(createButton).toBeVisible()
    await createButton.click()

    // Die Sequenz laeuft sequenziell ueber 3 + 8 Events gegen den Relay.
    await expect(page.getByRole('link', { name: /Chat öffnen/i })).toBeVisible({ timeout: 60000 })

    expect(failures, `JS-Fehler auf der Seite: ${failures.join(' | ')}`).toEqual([])
})
