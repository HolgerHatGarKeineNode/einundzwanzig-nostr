import { test, expect } from '@playwright/test'
import { connectBunker, installBunkerNip07 } from './support/bunker-nip07.js'

/**
 * Legt ein Vorstandsmitglied den privaten Chatraum eines Antrags an?
 *
 * Signiert wird per NIP-46 mit der Identitaet eines echten Vorstandsmitglieds
 * (NOSTR_BUNKER_URL / NOSTR_CLIENT_SK aus der .env) — der Lauf prueft damit den
 * realen Berechtigungsweg und nicht einen konstruierten Wegwerf-Schluessel.
 *
 * Voraussetzung, bewusst nicht automatisch gestartet, damit kein Lauf die
 * Arbeitsdatenbank oder den Prod-Relay trifft:
 *   ./scripts/zooid-testserver.sh start
 *   DB_DATABASE=<kopie> php artisan serve --port=8137
 *   NOSTR_SPACE_URL=ws://localhost:3341/
 *
 * Der Lauf braucht 11 Signaturen (Login, 3 Raum-Events, 7 Mitglieder). Steht
 * das Signier-Geraet nicht auf automatische Freigabe, muss jede bestaetigt
 * werden — entsprechend grosszuegige Timeouts.
 */

const PROPOSAL_SLUG = process.env.E2E_PROPOSAL_SLUG ?? 'lightning-watchtower-fur-mitglieder'

test.describe.configure({ timeout: 300000 })

test('Vorstand legt den privaten Chatraum an', async ({ page }) => {
    const failures = []
    const console_ = []
    page.on('pageerror', (e) => failures.push(String(e)))
    page.on('console', (m) => console_.push(`[${m.type()}] ${m.text().slice(0, 200)}`))
    page.on('requestfailed', (r) => console_.push(`[requestfailed] ${r.url().slice(0, 120)}`))
    const dumpLogs = () => console.log('--- Browser ---\n' + console_.slice(-20).join('\n'))

    const { signer, pubkey } = await connectBunker()
    console.log('Signiere als:', pubkey)

    try {
        await installBunkerNip07(page, signer, pubkey)
        await page.goto(`/association/project-support/${PROPOSAL_SLUG}`)

        await page.getByRole('button', { name: /Mit Nostr verbinden/i }).first().click()

        // Der Login endet mit einem vollstaendigen Reload.
        await expect(page.getByText('Chat zum Antrag')).toBeVisible({ timeout: 90000 })

        const createButton = page.getByRole('button', { name: /Chatraum anlegen/i })
        await expect(createButton).toBeVisible()
        await createButton.click()

        // 3 Raum-Events + 7 Mitglieder, sequenziell gegen den Relay.
        await expect(page.getByRole('link', { name: /Chat öffnen/i })).toBeVisible({ timeout: 180000 })

        expect(failures, `JS-Fehler auf der Seite: ${failures.join(' | ')}`).toEqual([])
    } finally {
        dumpLogs()
        try { await signer.close() } catch { /* Verbindung war schon zu */ }
    }
})
