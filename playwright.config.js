import { defineConfig } from '@playwright/test'

/**
 * E2E fuer die Nostr-Chatraeume der Projektunterstuetzungen.
 *
 * Bewusst schlank: Die App wird NICHT von Playwright gestartet. Der Test setzt
 * einen laufenden Dev-Server auf einer DB-Kopie und den lokalen zooid-Relay
 * voraus (scripts/zooid-testserver.sh) — beides mit Wegwerf-Daten. So kann kein
 * Lauf versehentlich die Arbeitsdatenbank oder den Prod-Relay beruehren.
 *
 * Host-Chromium statt Playwright-Download.
 */
export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    reporter: [['list']],
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8137',
        trace: 'retain-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: {
                browserName: 'chromium',
                launchOptions: {
                    executablePath: '/bin/chromium',
                    args: ['--no-sandbox'],
                },
            },
        },
    ],
})
