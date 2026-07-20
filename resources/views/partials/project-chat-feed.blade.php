{{--
    Eingebettete Ansicht des privaten Antragsraums (NIP-29).

    Die Huelle (`projectChatFeed`) ist in app.js registriert und leicht; sie laedt
    das Chat-SDK per dynamischem Import und registriert die Package-Komponenten
    erst danach — also NACH Alpines Start. Deshalb steckt das eigentliche Markup
    in `<template x-if="ready">`: Alpine initialisiert den Teilbaum erst beim
    Einfuegen und loest `x-data="projectChatRoomFeed(…)"` dann gegen die
    inzwischen gefuellte Registry auf.

    `wire:ignore`: Der Verlauf ist Alpine-gerendert. Ein Livewire-Morph (Abstimmen,
    Auszahlung eintragen) darf ihn nicht anfassen.

    Erwartete Variablen: $roomId, $roomName, $currentPubkey, $clientUrl.
--}}
<div wire:ignore
     x-data="projectChatFeed({
         currentPubkey: @js($currentPubkey),
         clientUrl: @js($clientUrl),
         spaceUrl: @js(config('group.space_url')),
         roomId: @js($roomId),
     })"
     class="mt-3">

    {{-- Ohne passende Chat-Session zuerst der Knopf: Das Anmelden am Relay
         verlangt eine Signatur, und ein Signier-Dialog beim blossen Seitenaufruf
         waere uebergriffig. --}}
    <div x-show="needsSigner" x-cloak>
        <flux:button size="sm" variant="filled" icon="chat-bubble-left-right" class="w-full"
                     x-on:click="boot()" x-bind:disabled="booting">
            Chat hier laden
        </flux:button>
    </div>

    <p x-show="booting" x-cloak class="mt-2 text-sm text-text-secondary" x-text="progress"></p>

    {{-- Scheitert die Insel, bleibt der Link auf den vollen Client daneben stehen
         — der Raum ist dann immer noch erreichbar. --}}
    <p x-show="bootError" x-cloak class="mt-2 text-sm text-red-400" x-text="bootError"></p>

    <template x-if="ready">
        <div x-data="projectChatRoomFeed(@js($roomId), @js($roomName))"
             class="flex h-[28rem] flex-col">

            {{-- Relay nicht erreichbar oder AUTH abgelehnt. --}}
            <template x-if="error">
                <flux:callout variant="danger" icon="exclamation-triangle" class="mb-2 shrink-0">
                    <flux:callout.text x-text="error"></flux:callout.text>
                    <x-slot name="actions">
                        <flux:button size="sm" variant="ghost" icon="arrow-path" x-on:click="retry()">
                            Erneut laden
                        </flux:button>
                    </x-slot>
                </flux:callout>
            </template>

            <div class="relative flex min-h-0 flex-1 flex-col">
                {{-- `flex-col-reverse` pinnt den Boden nativ: scrollTop 0 = neueste
                     Nachricht, aeltere voranstellen verschiebt die Leseposition nicht.
                     x-on:error.capture: Bild-Fehler steigen nicht auf, deshalb Capture. --}}
                <div x-ref="scroll" x-on:scroll="onScroll()" x-on:error.capture="unproxyImage($event)"
                     role="log" aria-live="polite" aria-relevant="additions" aria-label="Chat-Verlauf"
                     x-bind:aria-busy="loading && messages.length === 0"
                     class="flex min-h-0 flex-1 flex-col-reverse overflow-y-auto px-1 pb-2">

                    <div x-show="loading && messages.length === 0" class="space-y-3 pt-2">
                        <span class="sr-only" aria-live="polite">Verlauf wird geladen …</span>
                        @for ($i = 0; $i < 4; $i++)
                            <div class="flex gap-2">
                                <div class="skeleton size-8 shrink-0 rounded-full"></div>
                                <div class="flex-1 space-y-1.5 py-1">
                                    <div class="skeleton h-3 w-24"></div>
                                    <div class="skeleton h-3 w-2/3"></div>
                                </div>
                            </div>
                        @endfor
                    </div>

                    <template x-if="!loading && messages.length === 0">
                        <div class="empty-state mt-6 p-4 text-center">
                            <flux:icon.chat-bubble-left-right class="mx-auto size-7 text-text-tertiary"/>
                            <p class="mt-2 text-sm text-text-secondary">Noch keine Nachrichten in diesem Raum.</p>
                        </div>
                    </template>

                    <template x-for="m in messagesReversed" :key="m.id">
                        <div :class="m.showAuthor ? 'pt-2.5' : 'pt-0.5'">
                            @include('group::partials.chat-row', ['context' => 'room'])
                        </div>
                    </template>
                </div>

                <div class="pointer-events-none absolute inset-x-0 top-1 flex justify-center"
                     x-show="loadingMore" x-cloak x-transition.opacity>
                    <span class="surface-card rounded-full px-3 py-1 text-xs text-muted shadow-md">Laedt aeltere …</span>
                </div>

                <div class="pointer-events-none absolute inset-x-0 bottom-2 flex justify-center"
                     x-show="firstPaintDone && !atBottom" x-cloak x-transition.opacity>
                    <flux:button x-show="unread === 0" size="xs" variant="primary" square icon="arrow-down"
                                 class="icon-btn-touch pointer-events-auto" x-on:click="scrollToBottom()"
                                 aria-label="Zum Ende springen"/>
                    <flux:button x-show="unread > 0" x-cloak size="xs" variant="primary" icon="arrow-down"
                                 class="icon-btn-touch pointer-events-auto" x-on:click="scrollToBottom()"
                                 aria-label="Zum Ende springen">
                        <span x-text="unread"></span> neue
                    </flux:button>
                </div>
            </div>

            <div class="shrink-0 pt-2">
                {{-- Antworten / Zitieren / Bearbeiten: Kontextzeile ueber dem Composer. --}}
                <div x-show="membershipReady && joined && (replyTo || editingId)" x-cloak
                     class="surface-card mb-1 flex items-center gap-2 border-l-2 border-brand-500/60 px-3 py-1.5">
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-semibold text-brand-500"
                             x-text="editingId ? 'Nachricht bearbeiten' : (sharing ? 'Zitieren' : ('Antwort an ' + (replyTo?.name ?? '')))"></div>
                        <div class="truncate text-xs text-muted" x-show="replyTo" x-text="replyTo?.text"></div>
                    </div>
                    <flux:button size="xs" variant="ghost" icon="x-mark" class="icon-btn-touch"
                                 x-on:click="editingId ? cancelEdit() : clearReply()" aria-label="Abbrechen"/>
                </div>

                <div x-show="membershipReady && joined" x-cloak x-transition.opacity.duration.200ms>
                    @include('group::partials.chat-composer', ['context' => 'room'])
                </div>

                <div x-show="membershipReady && joined && sendError" x-cloak
                     class="mt-1 flex items-center justify-between gap-2 rounded-lg bg-red-500/10 px-3 py-1.5 text-xs text-red-400">
                    <span x-text="sendError"></span>
                    <button type="button" x-on:click="send()" class="pressable shrink-0 font-semibold text-brand-500 hover:underline">
                        Erneut senden
                    </button>
                </div>

                {{-- Kein „Beitreten"-Knopf: Der Antragsraum ist geschlossen (NIP-29
                     `closed`), Selbst-Beitritt per kind 9021 lehnt der Relay ab.
                     Mitglied wird man ausschliesslich ueber die Anlage des Raums. --}}
                <div x-show="membershipReady && !joined" x-cloak
                     class="surface-card p-3 text-sm text-muted">
                    Du bist kein Mitglied dieses Raums — nur Vorstand und Einreicher werden aufgenommen.
                </div>
            </div>

            {{-- Loeschen bestaetigen (NIP-09 ist unwiderruflich). Das Zeilen-Menue
                 ruft askDelete(), das genau dieses Modal oeffnet. --}}
            <flux:modal name="delete-message" class="max-w-sm">
                <div class="space-y-4">
                    <flux:heading size="lg">Nachricht löschen?</flux:heading>
                    <flux:text>Das lässt sich nicht rückgängig machen.</flux:text>
                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">Abbrechen</flux:button>
                        </flux:modal.close>
                        <flux:button variant="danger" x-on:click="confirmDelete()" x-bind:disabled="deleting">
                            Löschen
                        </flux:button>
                    </div>
                </div>
            </flux:modal>

            {{-- Lightbox fuer angeklickte Inline-Bilder (Zoom via lightboxZoom aus
                 dem Package). Schliessen ueber Hintergrund, ✕ und Escape — NICHT
                 ueber einen Klick aufs Bild, der wuerde den Doppeltipp zum Zoomen
                 verschlucken. --}}
            <div x-show="lightboxSrc" x-cloak x-transition.opacity
                 x-data="lightboxZoom" x-effect="lightboxSrc, reset()"
                 role="dialog" aria-modal="true" aria-label="Bild in voller Größe"
                 x-on:click.stop="panned || (lightboxSrc = null)"
                 x-on:keydown.escape.window="lightboxSrc = null"
                 x-on:pointerdown="onPointerDown($event)"
                 x-on:pointermove="onPointerMove($event)"
                 x-on:pointerup="onPointerUp($event)"
                 x-on:pointercancel="onPointerUp($event)"
                 x-on:wheel="onWheel($event)"
                 x-on:dblclick.stop="toggleZoom($event.clientX, $event.clientY)"
                 x-on:resize.window="clampPan()"
                 class="fixed inset-0 z-50 flex touch-none select-none items-center justify-center overscroll-contain bg-black/80 p-4">
                <img x-ref="img" x-bind:src="lightboxSrc" alt="" x-on:click.stop=""
                     class="max-h-full max-w-full rounded-xl will-change-transform"
                     x-bind:style="imageStyle"
                     x-on:error="$el.dataset.orig || ($el.dataset.orig = 1, $el.src = decodeURIComponent(($el.src.split('src=')[1] || '')))"/>
                <flux:button size="sm" variant="ghost" icon="x-mark"
                             class="icon-btn-touch !absolute top-4 right-4 bg-black/40 text-white"
                             x-on:click.stop="lightboxSrc = null" aria-label="Schließen"/>
            </div>

            <x-group::profile-card/>
        </div>
    </template>
</div>
