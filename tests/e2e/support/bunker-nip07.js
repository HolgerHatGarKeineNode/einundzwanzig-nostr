import { readFileSync } from 'node:fs'
import { BunkerSigner, parseBunkerInput } from 'nostr-tools/nip46'
import { hexToBytes } from 'nostr-tools/utils'

/**
 * Installiert ein NIP-07-`window.nostr`, das an einen NIP-46-Bunker (Amber)
 * weiterreicht.
 *
 * Damit testet der Lauf den ECHTEN Fall: Signiert wird mit der Identität eines
 * tatsaechlichen Vorstandsmitglieds, nicht mit einem konstruierten
 * Wegwerf-Schluessel, dem der Testrelay per Konfiguration Rechte zuschiebt.
 *
 * Der Schluessel verlaesst dabei nie das Signier-Geraet — Node haelt nur den
 * Client-Key der NIP-46-Verbindung.
 *
 * WICHTIG: Nur gegen den lokalen Relay verwenden. Mit dieser Identitaet gegen
 * group.einundzwanzig.space abgesetzte Events waeren echt und nicht
 * zurueckholbar.
 */
export async function connectBunker(envPath = '.env') {
    const env = Object.fromEntries(
        readFileSync(envPath, 'utf8')
            .split('\n')
            .filter((l) => l.includes('=') && ! l.trim().startsWith('#'))
            .map((l) => {
                const i = l.indexOf('=')
                return [l.slice(0, i).trim(), l.slice(i + 1).trim().replace(/^"|"$/g, '')]
            }),
    )

    if (! env.NOSTR_BUNKER_URL || ! env.NOSTR_CLIENT_SK) {
        throw new Error('NOSTR_BUNKER_URL und NOSTR_CLIENT_SK muessen in der .env stehen')
    }

    const pointer = await parseBunkerInput(env.NOSTR_BUNKER_URL)
    if (! pointer) {
        throw new Error('NOSTR_BUNKER_URL ist nicht parsebar')
    }

    const signer = BunkerSigner.fromBunker(hexToBytes(env.NOSTR_CLIENT_SK), pointer)
    await signer.connect()
    const pubkey = await signer.getPublicKey()

    return { signer, pubkey }
}

/**
 * Haengt den Bunker als window.nostr in die Seite.
 *
 * Die App laedt window.nostr.js von jsDelivr als Fallback fuer Besucher ohne
 * Erweiterung; es ueberschreibt ein bereits gesetztes window.nostr. Fuer den
 * Test blockiert — ein Nutzer mit echter Erweiterung hat dasselbe Ergebnis.
 */
export async function installBunkerNip07(page, signer, pubkey) {
    await page.route(/window\.nostr.*\.js/, (route) => route.abort())

    await page.exposeFunction('__nip07_getPublicKey', () => pubkey)
    await page.exposeFunction('__nip07_signEvent', (event) => signer.signEvent(event))

    await page.addInitScript(() => {
        window.nostr = {
            getPublicKey: () => window.__nip07_getPublicKey(),
            signEvent: (event) => window.__nip07_signEvent(event),
        }
    })
}
