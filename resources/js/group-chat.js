/**
 * Chat-Insel fuer die privaten Antragsraeume.
 *
 * Bewusst ein eigener Vite-Entry, der NUR auf der Detailseite einer
 * Projektunterstuetzung geladen wird: Der Import von @einundzwanzig/group hat
 * Seiteneffekte — er konfiguriert die welshman-Singletons global, registriert
 * eine AUTH-Policy fuer Relays und fasst localStorage sowie IndexedDB an. Auf
 * jeder Vereinsseite waere das falsch.
 */
import { registerNostrComponents } from '@einundzwanzig/group'
import { isAuthed } from '@einundzwanzig/group/auth-gate'
import { loginWithExtension } from '@einundzwanzig/group/session'

/**
 * Verbindet den Vereins-Login mit der welshman-Session des Packages.
 *
 * Der Verein authentifiziert serverseitig ueber ein signiertes kind-22242-Event
 * (App\Support\NostrAuth) und haelt das Ergebnis in der Laravel-Session. Die
 * Chat-Insel dagegen braucht eine welshman-Session im Browser, um sich per
 * NIP-42 gegenueber dem Gruppen-Relay auszuweisen. Beides nutzt dieselbe
 * NIP-07-Erweiterung, aber sie wissen nichts voneinander.
 *
 * Der NIP-98-Handoff des Packages wird hier bewusst NICHT verwendet — er
 * gehoert zu dessen eigenem Login-Pfad samt Redirect.
 *
 * @param {string} expectedPubkey hex-Pubkey des im Verein eingeloggten Nutzers
 */
async function bridgeVereinsLogin(expectedPubkey) {
    if (! expectedPubkey) {
        return
    }

    const stored = localStorage.getItem('pubkey')

    // Schon als der richtige Nutzer angemeldet — nichts zu tun. Ein erneuter
    // Aufruf wuerde nur eine ueberfluessige Signatur-Abfrage ausloesen.
    if (isAuthed(stored)) {
        try {
            if (JSON.parse(stored) === expectedPubkey) {
                return
            }
        } catch {
            // Kaputter Slot: wie "nicht angemeldet" behandeln.
        }
    }

    try {
        await loginWithExtension()
    } catch (error) {
        // Keine Erweiterung oder abgelehnt: Die Insel bleibt dann ohne Signer
        // und zeigt einen leeren Raum. Das ist ein erwartbarer Zustand, kein
        // Absturz — die Detailseite meldet es dem Nutzer.
        window.dispatchEvent(new CustomEvent('group-chat-signer-missing', {
            detail: { message: error?.message ?? String(error) },
        }))
    }
}

document.addEventListener('alpine:init', () => {
    registerNostrComponents(window.Alpine)
})

window.bridgeVereinsLogin = bridgeVereinsLogin
