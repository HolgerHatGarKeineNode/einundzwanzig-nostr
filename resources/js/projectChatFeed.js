import { Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm'

/**
 * Bettet den privaten NIP-29-Raum eines Antrags direkt in die Detailseite ein.
 *
 * ── Warum die Insel TRAEGE registriert wird ──────────────────────────────────
 * Das Package registriert seine Alpine-Bausteine per `registerNostrComponents`
 * im `alpine:init`-Hook, also VOR Alpines Start. Genau das geht hier nicht: Der
 * blosse Import von `@einundzwanzig/group` hat Seiteneffekte (welshman-Singletons,
 * NIP-42-Policy, IndexedDB-Cache, localStorage) und zieht ~950 KB. Im
 * `alpine:init` haengt das an app.js — und damit an JEDER Vereinsseite.
 *
 * Also andersherum: NUR diese leichte Komponente laeuft ueberall mit (kein
 * schwerer Top-Level-Import). Sie laedt das SDK per dynamischem `import()` und
 * ruft `registerNostrComponents` erst, wenn die Seite den Chat wirklich braucht
 * — also NACH Alpines Start. Das eigentliche Chat-Markup steckt in einem
 * `<template x-if="ready">`; Alpine initialisiert neu eingefuegte Teilbaeume
 * nach und loest `x-data="…"` dabei gegen die dann AKTUELLE Registry auf. Eine
 * spaete Registrierung wirkt dort also — anders als bei einem eigenen
 * Vite-Entry, der zu spaet kam und `x-data` wirkungslos liess (siehe
 * projectChatRoom.js).
 *
 * Alpine kommt bewusst aus `livewire.esm` — derselbe Import wie in app.js, also
 * garantiert dieselbe Instanz. `window.Alpine` waere geraten; ob es dieselbe
 * ist, prueft boot() und meldet eine Abweichung, statt sie zu verschlucken.
 *
 * ── Was der Ausschnitt NICHT kann ───────────────────────────────────────────
 * Threads, Umfragen, Bild-Upload, Zaps und Moderations-Info brauchen im Package
 * eigene Overlays, die an der Chat-Vollbildseite haengen (teils mit Routen, die
 * es im Verein nicht gibt). Statt tote Knoepfe stehen zu lassen, biegt
 * `deriveRoomChat()` genau diese Einstiege auf den vollen Chat-Client um bzw.
 * sagt per Toast, wo es weitergeht.
 */
export default function projectChatFeed(config) {
    return {
        /** Ist die Insel registriert und das Chat-Markup gerendert? */
        ready: false,
        booting: false,
        bootError: '',
        /** Was gerade passiert — der Erstlogin baut die Seite neu auf, das soll niemanden ueberraschen. */
        progress: 'Chat wird geladen …',
        /** Es fehlt eine welshman-Session — Laden erst auf Klick (Signier-Dialog). */
        needsSigner: false,

        init() {
            // Autostart nur mit passender, bereits vorhandener Session: Sonst
            // braucht loginWithExtension() eine Signatur, und ein Signier-Dialog
            // beim blossen Seitenaufruf waere uebergriffig. Ohne Session bleibt
            // der Knopf — der Nutzer entscheidet.
            if (!this.hasSession()) {
                this.needsSigner = true

                return
            }

            // Sonderfall aus derselben Reihenfolge-Falle (siehe
            // signInThenReload): Hat die Raum-Anlage das Package in DIESEM
            // Dokument geladen, bevor eine Session bestand, ist der Speicher als
            // Gast initialisiert — und `initStorage()` laeuft nie erneut. Dann
            // hilft nur der Neuaufbau, den die Anlage selbst nicht machen darf
            // (sie wuerde sich mitten im Publizieren abschneiden). Das Flag setzt
            // projectChatRoom.js; nach dem Neuaufbau ist es weg, also kein Kreis.
            if (window.__ezChatBootedWithoutSession) {
                window.location.reload()

                return
            }

            void this.boot()
        },

        /**
         * Passt die welshman-Session im localStorage zum eingeloggten Vereinsnutzer?
         * Der Verein weist sich serverseitig per kind 22242 aus, die Chat-Insel
         * braucht ihre eigene Browser-Session fuer NIP-42 gegenueber dem Relay.
         */
        hasSession() {
            try {
                return JSON.parse(localStorage.getItem('pubkey') ?? 'null') === config.currentPubkey
            } catch {
                return false
            }
        },

        async boot() {
            if (this.booting || this.ready) {
                return
            }
            this.booting = true
            this.bootError = ''

            try {
                // Zap-UI aus, BEVOR das Package laedt: `zapsEnabled()` liest das
                // Flag beim Bau der Komponente. Der Verein bettet weder Wallet
                // noch Zap-Sheet ein — ohne das Flag zeigten Zeile und Composer
                // Zap-Knoepfe, die ins Leere fuehren.
                window.__nostrZapsEnabled = false

                // Relay-Riegel, ebenfalls VOR dem Laden: `core.ts` liest
                // `__nostrRelays` beim Modul-Eval und speist daraus
                // DEFAULT_RELAYS / INDEXER_RELAYS / SIGNER_RELAYS.
                //
                // Ohne den Riegel fragt `loadRoomZaps` (feeds.ts) die Zap-Quittungen
                // mit `{"#e": [<IDs der Raum-Nachrichten>]}` bei
                // `uniq([spaceRelay, ...DEFAULT_RELAYS])` an — also zusaetzlich bei
                // vier oeffentlichen Relays. Das Zap-Flag oben hilft dagegen NICHT:
                // Es steuert nur die Oberflaeche, der Aufruf laeuft trotzdem. Damit
                // erfuehren vier fremde Betreiber Existenz, Menge und Zeitpunkt der
                // Nachrichten eines als private+closed+hidden deklarierten Raums —
                // samt IP des Vorstandsmitglieds. Inhalte nicht (IDs sind Hashes),
                // aber die Zusage "privat" waere trotzdem gebrochen.
                //
                // Deshalb: ALLE drei Listen auf den Space-Relay. Preis: Profile
                // (kind 0) werden nur noch dort aufgeloest, nicht mehr ueber
                // Indexer wie purplepag.es. Fuer einen Raum, dessen Teilnehmer
                // ohnehin alle Space-Mitglieder sind, ist das der richtige Tausch.
                window.__nostrRelays = {
                    default: [config.spaceUrl],
                    indexer: [config.spaceUrl],
                    signer: [config.spaceUrl],
                }

                if (!this.hasSession()) {
                    await this.signInThenReload()

                    return
                }

                // Ab hier ist gesichert: Die Session lag schon im localStorage,
                // BEVOR in diesem Dokument ein Package-Modul evaluiert wurde.
                // `initStorage()` liest den pubkey damit garantiert gefuellt.
                const [group, toastModule, storage] = await Promise.all([
                    import('@einundzwanzig/group'),
                    import('@einundzwanzig/group/toast'),
                    import('@einundzwanzig/group/storage'),
                ])

                // Abmelde-Haken (siehe resources/js/nostrLogout.js): Solange die
                // Insel laeuft, haelt sie eine offene IndexedDB-Verbindung — ein
                // blindes deleteDatabase bliebe daran haengen. clearCache()
                // schliesst sie erst und loescht dann.
                window.__ezChatClearCache = storage.clearCache

                if (window.Alpine && window.Alpine !== Alpine) {
                    throw new Error('Zwei Alpine-Instanzen auf der Seite — die Insel wuerde ins Leere registrieren.')
                }

                registerChatComponents(group.registerNostrComponents, toastModule.toast, config)

                this.needsSigner = false
                this.ready = true
            } catch (e) {
                this.bootError = e instanceof Error ? e.message : String(e)
            } finally {
                this.booting = false
            }
        },

        /**
         * Erstanmeldung am Chat-Relay — und danach ein Neuaufbau der Seite.
         *
         * ── Die Reihenfolge-Falle, zum zweiten Mal ──────────────────────────
         * Dieses Feature ist jetzt zweimal an derselben Fehlerklasse gescheitert:
         * erst an Alpine (Komponente registriert, nachdem Alpine schon lief),
         * jetzt am Speicher. Hier die Kette, damit sie beim naechsten Mal
         * sichtbar ist:
         *
         *   Der Import des Packages stoesst `initStorage()` an (core.ts:138).
         *   Das wartet auf `authReady` — die localStorage-Bindung von
         *   `pubkey`/`sessions` — und liest DANN EINMAL `pubkey.get()`
         *   (storage.ts:453-482). Ist der leer, initialisiert es als GAST und
         *   laeuft wegen eines One-Shot-Guards nie erneut: kein Cache, kein
         *   Verlauf nach dem Neuladen.
         *
         *   Wer sich also NACH dem Import anmeldet, kommt zu spaet — und zwar
         *   je nach Geraet mal so, mal so, weil dazwischen ein IndexedDB-Delete
         *   und ein dynamischer Import liegen. Genau dieser Wettlauf war der
         *   Fehler.
         *
         * ── Warum ein Neuaufbau und nicht etwas Feineres ────────────────────
         * Anmelden ohne das Package geht nicht: `session.ts` importiert selbst
         * `core.ts` (session.ts:24) — wer sich anmeldet, hat den Speicher schon
         * gebootet. Und das Package bietet keinen Weg, ihn danach nachzuziehen:
         * `initStorage()` ist der einzige Einstieg, hat den One-Shot-Guard, und
         * `startSync`/`dbName` sind modulprivat.
         *
         * Bleibt: die Session anlegen, sie steht damit im localStorage — und die
         * Seite neu aufbauen. Im NEUEN Dokument laeuft `core.ts` zwangslaeufig
         * erst nach dem Lesen dieses localStorage. Das ist keine Wartschleife,
         * die den Wettlauf verschiebt, sondern die Reihenfolge-Garantie des
         * Browsers: ein Dokument beginnt mit dem localStorage, den das vorige
         * hinterlassen hat. Denselben Weg geht der Vereins-Login selbst
         * (`auth-button.blade.php` laedt nach dem Anmelden neu), und der volle
         * Chat-Client kommt auf einer eigenen Login-Seite ebenfalls nur ueber
         * einen Seitenwechsel in diesen Zustand.
         *
         * Preis: einmal pro Geraet und Identitaet ein Neuaufbau beim ersten
         * „Chat hier laden". Danach greift der Autostart.
         */
        async signInThenReload() {
            this.progress = 'Am Chat-Relay anmelden …'

            const session = await import('@einundzwanzig/group/session')
            await session.loginWithExtension()

            // Kein Warten, kein Nachfassen: `loginWithNip07` setzt die Stores,
            // deren localStorage-Bindung schreibt im selben Zug. Steht der Slot
            // danach nicht, ist etwas grundsaetzlich anders als angenommen —
            // dann bricht der Boot mit Ansage ab, statt in einen halben
            // Zustand weiterzulaufen.
            if (!this.hasSession()) {
                throw new Error('Die Chat-Anmeldung wurde nicht gespeichert — bitte den Chat im vollen Client oeffnen.')
            }

            this.progress = 'Angemeldet — Seite wird neu aufgebaut …'
            window.location.reload()
        },
    }
}

/** Registrierung genau einmal pro Seitenleben (mehrere Inseln teilen sie sich). */
let registered = false

/**
 * Registriert die Package-Bausteine an Alpine — mit zwei bewussten Eingriffen.
 *
 * `registerNostrComponents` erwartet nur `data`/`magic`/`store`. Wir reichen
 * statt Alpine ein Objekt mit genau diesen drei Methoden herein und sehen dabei
 * jede Registrierung: So bekommen wir die `nostrRoomChat`-Factory in die Hand,
 * ohne das Package anzufassen (Alpine hat keinen Lese-Zugriff auf seine
 * Komponenten-Registry) — und koennen die `$img`-Magic ersetzen.
 */
function registerChatComponents(registerNostrComponents, toast, config) {
    if (registered) {
        return
    }
    registered = true

    const factories = {}

    registerNostrComponents({
        data: (name, factory) => {
            factories[name] = factory
            Alpine.data(name, factory)
        },
        magic: (name, callback) => {
            // `$img` uebersprungen: Die Magic baut `/img/<preset>?src=…` — einen
            // Zuschnitt-Proxy, den nur der Chat-Host betreibt. Im Verein gibt es
            // die Route nicht, jedes Avatar liefe erst in einen 404 und faende
            // erst ueber den Fehler-Fallback zum Original. Wir liefern die
            // Original-URL sofort.
            if (name === 'img') {
                return
            }
            Alpine.magic(name, callback)
        },
        store: (name, value) => Alpine.store(name, value),
    })

    Alpine.magic('img', () => (url) => (typeof url === 'string' ? url : ''))

    installModalGuard(config)

    Alpine.data('projectChatRoomFeed', deriveRoomChat(factories, toast, config))
}

/**
 * Netz gegen tote Knoepfe: Der Ausschnitt rendert nicht jedes Overlay, das die
 * Chat-Vollbildseite mitbringt.
 *
 * Das Package oeffnet JEDES seiner Overlays ueber `dispatchModal(name)`
 * (bridge.ts) — und das ist nichts weiter als ein `modal-show`-Event am
 * document, auf das Flux lauscht. Fehlt das Modal im DOM, passiert schlicht
 * nichts: Klick ins Leere, keine Meldung. Betroffen waren u.a.
 * „Nachricht entfernen" (Admin, `admin-delete-message`), „Melden"
 * (`report-message`), „Info" (`message-info`), Umfrage (`create-poll`).
 *
 * Bewusst EIN Wachposten statt einer Liste von Einzel-Umbiegungen: Eine Liste
 * muesste bei jeder Package-Aenderung nachgezogen werden und ist genau so
 * entstanden, dass ein Eintrag fehlte. Der Wachposten prueft stattdessen die
 * einzige Bedingung, auf die es ankommt — existiert ein Dialog dieses Namens? —
 * und schickt den Nutzer sonst in den vollen Client, wo das Overlay existiert.
 *
 * Er wird erst beim Boot der Insel registriert, laeuft also nur auf einer Seite
 * MIT Chat. Modale des Vereins bringen ihren Dialog selbst mit und laufen
 * unveraendert durch.
 */
let modalGuardInstalled = false

function installModalGuard(config) {
    if (modalGuardInstalled) {
        return
    }
    modalGuardInstalled = true

    document.addEventListener('modal-show', (event) => {
        const name = event.detail?.name
        if (!name || document.querySelector(`dialog[data-modal="${CSS.escape(name)}"]`)) {
            return
        }
        window.open(config.clientUrl + '/rooms/' + encodeURIComponent(config.roomId), '_blank', 'noopener')
    })
}

/**
 * Der Raum-Chat des Packages, auf den Ausschnitt zugeschnitten.
 *
 * Gespreizte Kopie statt Vererbung: Alpine injiziert seine Magics (`$refs`,
 * `$nextTick`, …) in das Objekt, das `x-data` liefert, und bindet `this` an den
 * reaktiven Proxy — die uebernommenen Methoden arbeiten also unveraendert
 * weiter. `base` dient nur als Trampolin fuer die Original-Implementierung.
 */
function deriveRoomChat(factories, toast, config) {
    return (h, roomName) => {
        const base = factories.nostrRoomChat(h, roomName, null)

        /** Weiterleitung in den vollen Chat-Client (neuer Tab). */
        const openFullClient = (path) => {
            window.open(config.clientUrl + (path ?? '/rooms/' + encodeURIComponent(String(h))), '_blank', 'noopener')
        }

        return {
            ...base,

            /**
             * Thread-Ansicht: Der Ausschnitt hat keine zweite Ebene (und die
             * Package-Implementierung spiegelt die Thread-URL per replaceState
             * in die Adressleiste — hier waere das die Antragsseite, und ein
             * Reload landete auf einer Route, die es im Verein nicht gibt).
             * Der volle Client kann es, also dorthin.
             */
            openThread(m) {
                let path = null
                try {
                    path = base.threadHref.call(this, m)
                } catch {
                    // Unvollstaendige Nachricht → ohne Tiefenlink in den Raum.
                }
                openFullClient(path)
            },

            /**
             * Bild anhaengen braucht Zuschnitt-Overlay und Blossom-Upload —
             * beides haengt an der Vollbildseite und ihren Einstellungen.
             *
             * Bewusst eine eigene Umbiegung und NICHT dem Modal-Wachposten
             * ueberlassen: Der Zuschnitt ist kein Flux-Modal, sondern ein
             * `x-show`-Overlay — er feuert nie ein `modal-show`.
             */
            pickImage(input) {
                if (input) {
                    input.value = ''
                }
                toast('Bilder haengt der volle Chat-Client an.', 'info')
            },

            pasteImage(event) {
                if (event?.clipboardData?.files?.length) {
                    event.preventDefault()
                    toast('Bilder haengt der volle Chat-Client an.', 'info')
                }
            },

            /**
             * Bilder IM Nachrichtentext baut das Package direkt ueber den
             * Zuschnitt-Proxy (`/img/<preset>?src=…`), nicht ueber die
             * `$img`-Magic — den Weg oben abzubiegen reicht dafuer nicht. Im
             * Verein gibt es die Proxy-Route nicht, also faellt das Bild beim
             * 404 auf die Original-URL zurueck. Denselben Fallback fahren die
             * Lightbox und das Avatar des Packages.
             */
            unproxyImage(event) {
                const img = event?.target
                if (!(img instanceof HTMLImageElement) || !img.matches('img.chat-image, img.chat-emoji')) {
                    return
                }
                const original = decodeURIComponent((img.getAttribute('src') ?? '').split('src=')[1] ?? '')
                if (original && img.src !== original) {
                    img.src = original
                }
            },
        }
    }
}
