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
import { createRoom, addRoomMember } from '@einundzwanzig/group/groups'

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

/**
 * Legt den privaten Chatraum einer Projektunterstuetzung an.
 *
 * Die Sequenz (9007 Create -> 9002 Metadaten -> 9021 Beitritt, danach je
 * Mitglied ein 9000) kommt aus dem Package; sie ist dort erprobt und behandelt
 * "already/duplicate" auf Create und Join als Erfolg, auf die Metadaten aber
 * nicht — ein Wiederholungsversuch vervollstaendigt damit denselben Raum,
 * statt einen zweiten Waisen anzulegen.
 *
 * Signiert wird im Browser vom eingeloggten Vorstandsmitglied. Der Relay
 * akzeptiert 9007 und 9000 nur von einem Pubkey mit relay-weitem can_manage —
 * das haben die Vorstandsmitglieder.
 */
function projectChatRoom(config) {
    return {
        busy: false,
        error: '',
        progress: '',

        async create() {
            if (this.busy) {
                return
            }
            this.busy = true
            this.error = ''

            try {
                await bridgeVereinsLogin(config.currentPubkey)

                this.progress = 'Raum wird angelegt …'
                const err = await createRoom(config.spaceUrl, {
                    h: config.roomId,
                    name: config.roomName,
                    about: config.roomAbout,
                    picture: '',
                    // Alle drei sind noetig, nicht nur private: `private` schuetzt
                    // die Nachrichten, aber der Relay laesst die Raum-Metadaten
                    // (kind 39000) ungeprueft durch — ohne `hidden` koennte jedes
                    // Vereinsmitglied Name und Gegenstand des Antrags lesen.
                    isPrivate: true,
                    isClosed: true,
                    isHidden: true,
                    isRestricted: false,
                })

                if (err) {
                    throw new Error(err)
                }

                // Sequenziell, nicht parallel: Der member-only-Relay verliert bei
                // gleichzeitigen Publishes Events im AUTH-Handshake.
                let done = 0
                for (const pubkey of config.memberPubkeys) {
                    this.progress = `Mitglieder werden aufgenommen (${++done}/${config.memberPubkeys.length}) …`
                    const memberErr = await addRoomMember(config.spaceUrl, config.roomId, pubkey)
                    if (memberErr && ! /already|duplicate/i.test(memberErr)) {
                        throw new Error(`Mitglied ${pubkey.slice(0, 8)}…: ${memberErr}`)
                    }
                }

                this.progress = 'Wird gespeichert …'
                this.$wire.call('storeChatRoom', config.roomId)
            } catch (e) {
                this.error = e instanceof Error ? e.message : String(e)
                this.progress = ''
            } finally {
                this.busy = false
            }
        },
    }
}

document.addEventListener('alpine:init', () => {
    registerNostrComponents(window.Alpine)
    window.Alpine.data('projectChatRoom', projectChatRoom)
})

window.bridgeVereinsLogin = bridgeVereinsLogin
