/**
 * Legt den privaten Chatraum einer Projektunterstuetzung an (NIP-29).
 *
 * Bewusst LEICHT: Diese Datei wird in app.js registriert und laeuft damit auf
 * jeder Vereinsseite mit — sie darf nichts Schweres importieren. Das Chat-SDK
 * (@welshman/*, ~950 KB) kommt per dynamischem Import erst beim Klick.
 *
 * Der Umweg ueber einen eigenen Vite-Entry, der nur auf dieser Seite laedt, hat
 * sich als Sackgasse erwiesen: Beide Dateien sind ES-Module, und ein Modul mit
 * grossem Abhaengigkeitsbaum ist erst fertig, wenn alle Importe aufgeloest sind.
 * app.js war laengst durch und hatte Alpine gestartet — die Komponente kam zu
 * spaet, `x-data` blieb wirkungslos und der Knopf tat nichts. Die Position im
 * HTML aendert daran nichts; entscheidend ist die Ausfuehrungsreihenfolge.
 */
export default function projectChatRoom(config) {
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
            this.progress = 'Chat wird geladen …'

            try {
                // Erst hier faellt das SDK an — nicht beim Seitenaufbau.
                const [{ createRoom, addRoomMember }, { loginWithExtension }, { isAuthed }] = await Promise.all([
                    import('@einundzwanzig/group/groups'),
                    import('@einundzwanzig/group/session'),
                    import('@einundzwanzig/group/auth-gate'),
                ])

                await this.ensureSigner(loginWithExtension, isAuthed, config.currentPubkey)

                this.progress = 'Raum wird angelegt …'
                const err = await createRoom(config.spaceUrl, {
                    h: config.roomId,
                    name: config.roomName,
                    about: config.roomAbout,
                    picture: '',
                    // Alle drei, nicht nur private: private schuetzt die
                    // Nachrichten, aber der Relay laesst die Raum-Metadaten
                    // (kind 39000) ungeprueft durch — ohne hidden koennte jedes
                    // Vereinsmitglied Name und Gegenstand des Antrags lesen.
                    isPrivate: true,
                    isClosed: true,
                    isHidden: true,
                    isRestricted: false,
                })

                if (err) {
                    throw new Error(err)
                }

                // Sequenziell: Der member-only-Relay verliert bei gleichzeitigen
                // Publishes Events im AUTH-Handshake.
                let done = 0
                for (const pubkey of config.memberPubkeys) {
                    this.progress = `Mitglieder werden aufgenommen (${++done}/${config.memberPubkeys.length}) …`
                    const memberErr = await addRoomMember(config.spaceUrl, config.roomId, pubkey)
                    if (memberErr && ! /already|duplicate/i.test(memberErr)) {
                        throw new Error(`Mitglied ${pubkey.slice(0, 8)}…: ${memberErr}`)
                    }
                }

                this.progress = 'Wird gespeichert …'
                await this.$wire.call('storeChatRoom', config.roomId)
            } catch (e) {
                this.error = e instanceof Error ? e.message : String(e)
                this.progress = ''
            } finally {
                this.busy = false
            }
        },

        /**
         * Verbindet den Vereins-Login mit der welshman-Session des Packages.
         *
         * Der Verein weist sich serverseitig ueber ein signiertes kind-22242-
         * Event aus (App\Support\NostrAuth), die Chat-Bibliothek braucht eine
         * eigene Session im Browser fuer NIP-42 gegenueber dem Relay. Beides
         * nutzt dieselbe NIP-07-Erweiterung, weiss aber nichts voneinander.
         */
        async ensureSigner(loginWithExtension, isAuthed, expectedPubkey) {
            const stored = localStorage.getItem('pubkey')

            if (isAuthed(stored)) {
                try {
                    if (JSON.parse(stored) === expectedPubkey) {
                        return
                    }
                } catch {
                    // Kaputter Slot: wie "nicht angemeldet" behandeln.
                }
            }

            await loginWithExtension()
        },
    }
}
