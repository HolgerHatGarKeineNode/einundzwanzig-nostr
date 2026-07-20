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
                // Die Anlage meldet sich BEWUSST erst nach dem Import an (der
                // Signer wird erst zum Publizieren gebraucht) — fuer sie ist das
                // folgenlos, sie haengt an keinem Cache. Fuer die Insel auf
                // derselben Seite ist es das nicht: Der Import initialisiert den
                // Event-Speicher des Packages EINMALIG, ohne Session also als
                // Gast (siehe projectChatFeed.js, signInThenReload). Deshalb hier
                // nur der Vermerk — die Insel baut die Seite danach einmal neu
                // auf. Ein Neuaufbau an dieser Stelle verbietet sich: Er schnitte
                // die laufende Anlage mitten im Publizieren ab.
                window.__ezChatBootedWithoutSession = ! localStorage.getItem('pubkey')

                // Erst hier faellt das SDK an — nicht beim Seitenaufbau.
                // roomCategories.ts ist winzig und welshman-frei, wird hier aber
                // bewusst ebenfalls dynamisch geholt: Ein statischer Import
                // zoege das Modul in app.js, und app.js soll ausser der
                // Alpine-Huelle nichts vom Chat kennen.
                const [{ createRoom, addRoomMember }, { loginWithExtension }, { isAuthed }, { projectSupportTags }] = await Promise.all([
                    import('@einundzwanzig/group/groups'),
                    import('@einundzwanzig/group/session'),
                    import('@einundzwanzig/group/auth-gate'),
                    import('@einundzwanzig/group/roomCategories'),
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
                    // Kategorie-Marker fuers 39000: ["t","project-support"] und
                    // die Bindung ["i","proposal:<id>"]. Nur beim ANLEGEN — beim
                    // Bearbeiten kopiert makeRoomEditEvent die vorhandenen Tags
                    // ohnehin mit. Die Antrags-ID ist die Datenbank-ID (wie die
                    // Raum-ID), nicht der Slug: Der Slug haengt am Namen und
                    // wanderte bei einer Umbenennung mit, das Tag steht aber
                    // unwiderruflich im Event.
                    extraTags: projectSupportTags(config.proposalId),
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
