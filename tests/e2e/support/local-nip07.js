import { finalizeEvent, getPublicKey } from 'nostr-tools/pure'
import { hexToBytes } from 'nostr-tools/utils'

/**
 * Installiert ein NIP-07-`window.nostr`, das mit einem WEGWERF-Schluessel
 * signiert.
 *
 * Bewusst nicht der Bunker aus der .env: Der Lauf soll ohne Freigabe am
 * Signier-Geraet durchlaufen, und fuer das Lesen/Schreiben im Raum genuegt eine
 * Identitaet, die der lokale Relay per `allowpubkey` kennt und die im Raum
 * Mitglied ist — genau der Antragsteller-Schluessel aus
 * scripts/zooid-testserver.sh.
 *
 * NUR gegen den lokalen Relay. Der Schluessel ist oeffentlich bekannt.
 */
export const SUBMITTER_SEC = 'b7550c0b4c20e479e317ce4fb9bb5c144577f772fce12e48815fb5d71c637781'

export async function installLocalNip07(page, secretHex = SUBMITTER_SEC) {
    const sk = hexToBytes(secretHex)
    const pubkey = getPublicKey(sk)

    // Die App laedt window.nostr.js von jsDelivr als Fallback fuer Besucher ohne
    // Erweiterung; es ueberschriebe ein bereits gesetztes window.nostr.
    await page.route(/window\.nostr.*\.js/, (route) => route.abort())

    await page.exposeFunction('__nip07_getPublicKey', () => pubkey)
    await page.exposeFunction('__nip07_signEvent', (event) => finalizeEvent(event, sk))

    await page.addInitScript(() => {
        window.nostr = {
            getPublicKey: () => window.__nip07_getPublicKey(),
            signEvent: (event) => window.__nip07_signEvent(event),
        }
    })

    return pubkey
}
