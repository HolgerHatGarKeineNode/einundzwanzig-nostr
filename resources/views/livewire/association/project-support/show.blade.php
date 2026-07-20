<?php

use App\Enums\ProjectProposalStatus;
use App\Livewire\Traits\WithNostrAuth;
use App\Models\ProjectProposal;
use App\Models\Vote;
use App\Support\NostrAuth;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    use WithNostrAuth;

    #[Locked]
    public ProjectProposal $projectProposal;

    #[Locked]
    public bool $ownVoteExists = false;

    public ?int $payoutSats = null;

    public function mount(ProjectProposal $projectProposal): void
    {
        $this->projectProposal = $projectProposal;
        $this->payoutSats = $projectProposal->sats_paid ?: $projectProposal->support_in_sats;
        if (NostrAuth::check()) {
            $this->currentPubkey = NostrAuth::pubkey();
            $this->isAllowed = true;
            $this->mountWithNostrAuth();
            $this->ownVoteExists = Vote::query()
                ->where('project_proposal_id', $this->projectProposal->id)
                ->where('einundzwanzig_pleb_id', $this->currentPleb->id)
                ->exists();
        }
    }

    /**
     * Alle Stimmen zum Antrag in EINER Abfrage, inklusive Profil des Stimmenden.
     * Vorher liefen dafür zwei Abfragen mit whereHas/whereDoesntHave auf dieselbe
     * Tabelle, und die Namen fehlten trotzdem.
     */
    #[Computed]
    public function votes(): Collection
    {
        $boardPlebIds = ProjectProposal::boardPlebIds();

        return Vote::query()
            ->where('project_proposal_id', $this->projectProposal->id)
            ->with('einundzwanzigPleb.profile')
            ->get()
            ->each(fn (Vote $vote) => $vote->setAttribute(
                'is_board_vote',
                in_array($vote->einundzwanzig_pleb_id, $boardPlebIds, true)
            ));
    }

    #[Computed]
    public function boardVotes(): Collection
    {
        return $this->votes->where('is_board_vote', true);
    }

    #[Computed]
    public function otherVotes(): Collection
    {
        return $this->votes->where('is_board_vote', false);
    }

    /**
     * Der abgeleitete Status des Antrags — dieselbe Regel wie in der Übersicht.
     */
    #[Computed]
    public function status(): ProjectProposalStatus
    {
        $this->projectProposal->setAttribute('board_approvals_count', $this->boardVotes->where('value', true)->count());
        $this->projectProposal->setAttribute('board_rejections_count', $this->boardVotes->where('value', false)->count());

        return $this->projectProposal->status();
    }

    /**
     * Darf der Betrachter die Kontaktangabe des Einreichers sehen?
     * Kontaktdaten sind personenbezogen und stehen auf einer öffentlich
     * erreichbaren Seite — deshalb nur Vorstand und Einreicher.
     */
    #[Computed]
    public function canSeeContact(): bool
    {
        return Gate::forUser(NostrAuth::user())->allows('viewContact', $this->projectProposal);
    }

    /**
     * Ist der Betrachter überhaupt stimmberechtigt? Bewusst NICHT über die
     * create-Policy: die verneint, sobald eine Stimme existiert. Wer schon
     * abgestimmt hat, soll seine Stimme weiterhin sehen — nur eben nicht
     * ändern können.
     */
    #[Computed]
    public function isVoter(): bool
    {
        return NostrAuth::user()?->getPleb() !== null;
    }

    /**
     * Zählt die Stimme des Betrachters zur bindenden Mehrheit — oder ist sie
     * Stimmungsbild? Beide dürfen abstimmen, nur eben mit anderem Gewicht.
     */
    #[Computed]
    public function voteCountsTowardsMajority(): bool
    {
        return (bool) NostrAuth::user()?->getPleb()?->isBoardMember();
    }

    /**
     * Darf jetzt eine Stimme abgegeben werden? Dieselbe Prüfung, die
     * handleApprove() serverseitig macht — damit niemand einen Knopf sieht,
     * der für ihn in einer 403 endet.
     */
    #[Computed]
    public function canVote(): bool
    {
        $nostrUser = NostrAuth::user();

        if (! $nostrUser || ! $nostrUser->getPleb()) {
            return false;
        }

        return Gate::forUser($nostrUser)->allows('create', [Vote::class, $this->projectProposal]);
    }

    /**
     * Die eigene Stimme, sofern abgegeben — damit die Seite sagen kann, WIE
     * abgestimmt wurde, statt nur dass abgestimmt wurde.
     */
    #[Computed]
    public function ownVote(): ?Vote
    {
        if (! $this->currentPleb) {
            return null;
        }

        return $this->votes->firstWhere('einundzwanzig_pleb_id', $this->currentPleb->id);
    }

    #[Computed]
    public function canManage(): bool
    {
        return Gate::forUser(NostrAuth::user())->allows('accept', $this->projectProposal);
    }

    /**
     * Darf jetzt ausgezahlt werden? Dieselbe Prüfung, die recordPayout()
     * serverseitig macht — die Oberfläche zeigt das Formular sonst für einen
     * Antrag, den niemand auszahlen darf.
     */
    #[Computed]
    public function canPayout(): bool
    {
        return Gate::forUser(NostrAuth::user())->allows('payout', $this->projectProposal);
    }

    /**
     * Darf der Betrachter den privaten Chatraum anlegen? Nur Vorstand, und nur
     * solange es keinen gibt.
     */
    #[Computed]
    public function canCreateChatRoom(): bool
    {
        return Gate::forUser(NostrAuth::user())->allows('createChatRoom', $this->projectProposal);
    }

    /**
     * Darf der Betrachter den Chatraum sehen und nutzen? Vorstand und Einreicher.
     */
    #[Computed]
    public function canSeeChatRoom(): bool
    {
        return Gate::forUser(NostrAuth::user())->allows('viewChatRoom', $this->projectProposal);
    }

    /**
     * Die Pubkeys, die in den Raum gehören — für die Insel, die daraus die
     * kind-9000-Events baut.
     *
     * @return list<string>
     */
    #[Computed]
    public function chatRoomMemberPubkeys(): array
    {
        return $this->projectProposal->nostrGroupMemberPubkeys();
    }

    /**
     * Vermerkt den angelegten Chatraum am Antrag.
     *
     * Wird von der Insel gerufen, nachdem die NIP-29-Events durch sind. Die
     * Raum-ID kommt NICHT aus dem Aufruf, sondern wird hier neu berechnet: Eine
     * öffentliche Livewire-Methode ist direkt aufrufbar, und ein gefälschter
     * Wert würde den Antrag dauerhaft auf einen fremden Raum zeigen lassen.
     * Der übergebene Wert dient nur als Abgleich.
     */
    public function storeChatRoom(string $roomId): void
    {
        Gate::forUser(NostrAuth::user())->authorize('createChatRoom', $this->projectProposal);

        $expected = $this->projectProposal->nostrGroupId();

        if ($roomId !== $expected) {
            Flux::toast(
                text: 'Der gemeldete Raum passt nicht zu diesem Antrag. Es wurde nichts gespeichert.',
                variant: 'danger',
            );

            return;
        }

        // Direkte Zuweisung: nostr_group_h ist bewusst nicht in $fillable.
        $this->projectProposal->nostr_group_h = $expected;
        $this->projectProposal->nostr_group_created_at = now();
        $this->projectProposal->save();

        unset($this->canCreateChatRoom, $this->canSeeChatRoom);

        Flux::toast('Chatraum angelegt.');
    }

    /**
     * Trägt die Auszahlung ein. Nur Vorstand — die Berechtigung wird hier
     * serverseitig geprüft, weil jede öffentliche Livewire-Methode direkt
     * aufrufbar ist, unabhängig davon, was die View rendert.
     */
    public function recordPayout(): void
    {
        // 'payout' statt 'accept': verlangt zusätzlich die absolute Mehrheit des
        // Vorstands. Geld darf nur einem Beschluss folgen — ohne diese Prüfung
        // könnte ein einzelnes Vorstandsmitglied einen noch laufenden oder sogar
        // abgelehnten Antrag auszahlen.
        Gate::forUser(NostrAuth::user())->authorize('payout', $this->projectProposal);

        $this->validate([
            'payoutSats' => 'required|integer|min:1',
        ], [
            'payoutSats.min' => 'Der ausgezahlte Betrag muss größer als 0 sein.',
        ]);

        // Direkte Zuweisung statt update(): sats_paid ist bewusst NICHT in $fillable,
        // damit eine Auszahlung nie über Mass Assignment gesetzt werden kann.
        $this->projectProposal->sats_paid = (int) $this->payoutSats;
        $this->projectProposal->save();

        $this->forgetVoteCache();
        Flux::modals()->close();
        Flux::toast('Auszahlung eingetragen.');
    }

    /**
     * Nimmt eine eingetragene Auszahlung zurück (Korrektur einer Fehleingabe).
     */
    public function revertPayout(): void
    {
        Gate::forUser(NostrAuth::user())->authorize('accept', $this->projectProposal);

        $this->projectProposal->sats_paid = 0;
        $this->projectProposal->save();
        $this->payoutSats = $this->projectProposal->support_in_sats;

        $this->forgetVoteCache();
        Flux::toast('Auszahlung zurückgenommen.');
    }

    private function forgetVoteCache(): void
    {
        unset($this->votes, $this->boardVotes, $this->otherVotes, $this->status, $this->ownVote);
    }

    public function handleApprove(): void
    {
        $nostrUser = NostrAuth::user();

        if (! $nostrUser || ! $nostrUser->getPleb()) {
            return;
        }

        Gate::forUser($nostrUser)->authorize('create', [Vote::class, $this->projectProposal]);

        $executed = RateLimiter::attempt(
            'voting:'.request()->ip(),
            10,
            function () {},
        );

        if (! $executed) {
            abort(429, 'Too many voting attempts.');
        }

        Vote::query()->updateOrCreate([
            'project_proposal_id' => $this->projectProposal->id,
            'einundzwanzig_pleb_id' => $this->currentPleb->id,
        ], [
            'value' => true,
        ]);
        $this->ownVoteExists = true;
        $this->forgetVoteCache();
        Flux::modals()->close();
        Flux::toast('Deine Stimme wurde gezählt.');
    }

    public function handleNotApprove(): void
    {
        $nostrUser = NostrAuth::user();

        if (! $nostrUser || ! $nostrUser->getPleb()) {
            return;
        }

        Gate::forUser($nostrUser)->authorize('create', [Vote::class, $this->projectProposal]);

        $executed = RateLimiter::attempt(
            'voting:'.request()->ip(),
            10,
            function () {},
        );

        if (! $executed) {
            abort(429, 'Too many voting attempts.');
        }

        Vote::query()->updateOrCreate([
            'project_proposal_id' => $this->projectProposal->id,
            'einundzwanzig_pleb_id' => $this->currentPleb->id,
        ], [
            'value' => false,
        ]);
        $this->ownVoteExists = true;
        $this->forgetVoteCache();
        Flux::modals()->close();
        Flux::toast('Deine Stimme wurde gezählt.');
    }
}
?>

<div>
    @php
        $threshold = App\Models\ProjectProposal::boardVoteThreshold();
        $approvals = $this->boardVotes->where('value', true);
        $rejections = $this->boardVotes->where('value', false);
        $otherApprovals = $this->otherVotes->where('value', true);
        $otherRejections = $this->otherVotes->where('value', false);
        $leading = $rejections->count() > $approvals->count() ? 'rejected' : 'approved';
        $filled = min($leading === 'rejected' ? $rejections->count() : $approvals->count(), $threshold);
    @endphp

    <div class="w-full max-w-[1600px] mx-auto">
        <div class="mb-6">
            <flux:button :href="route('association.projectSupport')" variant="ghost" size="sm" icon="chevron-left">
                Zurück zur Übersicht
            </flux:button>
        </div>

        {{-- ── Chat-Band ──────────────────────────────────────────────────────
             Der private Antragsraum steht ganz oben, VOR Titel und Eckdaten —
             fuer den Kreis, der ihn ueberhaupt sieht (Vorstand, Einreicher),
             ist er der Grund, die Seite zu oeffnen.

             Der Preis waere, dass der Antrag selbst unter die Falz rutscht.
             Deshalb ist das Band aufklappbar und startet auf dem Telefon
             ZUGEKLAPPT: Sichtbar bleibt eine Zeile (~64px), der Chat kostet
             also Platz fuer eine Kopfzeile statt fuer 448px Verlauf. Ab 48rem
             (md) startet es offen — dort ist genug Hoehe, dass Titel und
             Fördersumme trotzdem im ersten Bildschirm liegen.

             Das Aufklappen ist rein optisch: Was die Insel laedt und wann,
             entscheidet weiterhin sie selbst (siehe projectChatFeed.js) — ein
             zugeklapptes Band laedt nichts nach, ein aufgeklapptes auch nicht,
             solange keine Chat-Session besteht.

             Das Gate bleibt serverseitig: Wer den Raum nicht sehen darf,
             bekommt dieses Markup gar nicht erst. --}}
        @if($this->canSeeChatRoom || $this->canCreateChatRoom)
            {{-- Die Chat-Vollbildseite liegt auf dem Space-Host, nicht im
                 Verein: ws(s):// -> https://.
                 Bewusst die einzeilige Form der php-Direktive: Weiter oben
                 steht bereits eine einzeilige ohne Abschluss. Blades
                 Raw-Block-Regex paart die erste Oeffnung mit dem NAECHSTEN
                 Abschluss — ein Block hier verschluckte alles dazwischen, und
                 die Datei liesse sich nicht mehr uebersetzen. --}}
            @php($chatClientUrl = rtrim(str_replace(['ws://', 'wss://'], 'https://', config('group.space_url', '')), '/'))
            {{-- Ohne Raum ist der Inhalt drei Zeilen lang — der klappt auch auf
                 dem Telefon nichts weg und startet deshalb offen. --}}
            @php($chatBandInitiallyOpen = $projectProposal->hasNostrGroup() ? "window.matchMedia('(min-width: 48rem)').matches" : 'true')

            {{-- Bewusst KEIN overflow-hidden zum Runden der Kopfzeile: Die Insel
                 legt Emoji-Panel, Mention-Liste und Aktionsleiste absolut ueber
                 den Verlauf; ein clippender Vorfahr schnitte sie ab. Stattdessen
                 rundet die Kopfzeile sich selbst. --}}
            <div class="mb-6 rounded-xl border border-border-subtle border-l-2 border-l-orange-500 bg-bg-surface"
                 x-data="{ open: {{ $chatBandInitiallyOpen }} }">

                {{-- Die ganze Kopfzeile ist der Schalter: ein 44px hohes Ziel
                     ueber die volle Breite. Der Ausweich-Link steht bewusst NICHT
                     hier drin — verschachtelte Bedienelemente in einem Button
                     sind ungueltig; er sitzt im Panel neben dem Verlauf.

                     `wire:ignore.self`: Livewires Morph schriebe die Attribute
                     des Knopfes sonst auf den Server-Stand zurueck — nach der
                     ersten Stimmabgabe meldete `aria-expanded` „false", waehrend
                     das Panel offen dasteht. Die Kinder morpht Livewire weiter
                     (`childrenOnly`), sie sind rein serverseitig. --}}
                <button type="button" wire:ignore.self
                        class="group flex w-full min-h-11 items-center gap-3 rounded-t-xl px-4 py-3 text-left transition-colors duration-150 hover:bg-bg-elevated focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 motion-reduce:transition-none md:px-5"
                        x-on:click="open = ! open"
                        aria-expanded="false"
                        :aria-expanded="open ? 'true' : 'false'"
                        aria-controls="chat-band-panel">
                    <flux:icon name="chat-bubble-left-right" variant="micro"
                               class="shrink-0 text-orange-500" aria-hidden="true"/>
                    <span class="min-w-0 flex-1">
                        <span class="block text-base font-semibold leading-tight text-text-primary">
                            Chat zum Antrag
                        </span>
                        <span class="mt-0.5 block truncate text-sm text-text-tertiary">
                            @if($projectProposal->hasNostrGroup())
                                Privater Raum — nur Vorstand und Einreicher
                            @else
                                Noch kein Raum angelegt
                            @endif
                        </span>
                    </span>
                    {{-- Der Pfeil dreht sich rein per CSS am aria-Zustand des
                         Knopfes — keine zweite Wahrheit neben `open`, und nichts,
                         was ein Morph zuruecksetzen koennte. --}}
                    <span class="inline-flex shrink-0 text-text-tertiary transition-transform duration-150 group-aria-expanded:rotate-180 motion-reduce:transition-none">
                        <flux:icon name="chevron-down" variant="micro" aria-hidden="true"/>
                    </span>
                </button>

                {{-- Zugeklappt kommt als INLINE-Stil vom Server, nicht als Klasse
                     und nicht per x-cloak — beide waeren hier falsch:

                     `hidden md:block`: x-show setzt beim Oeffnen nur
                     `display: ''` zurueck; die Klasse `hidden` bliebe stehen und
                     das Panel zu.

                     `x-cloak`: Livewires Morph (patchAttributes) uebertraegt die
                     Attribute des neuen HTML auf ein VERSTECKTES Element zurueck
                     — nach der ersten Stimmabgabe stuende x-cloak wieder da, und
                     Alpine entfernt es nur beim Initialisieren. Das Panel liesse
                     sich danach nie wieder oeffnen. Ein sichtbares Panel ruehrt
                     der Morph nicht an (`_x_isShown`-Weiche), und `display:none`
                     ist genau die Eigenschaft, die x-show selbst setzt und
                     wegnimmt — der Rueckschlag des Morphs ist damit ein no-op.

                     Preis: Bis Alpine laeuft, ist das Panel auf JEDEM Viewport
                     zu. Sein Inhalt haengt ohnehin an Alpine. --}}
                <div id="chat-band-panel" x-show="open" style="display: none"
                     class="border-t border-border-subtle px-4 pb-4 md:px-5 md:pb-5">

                    @if($projectProposal->hasNostrGroup())
                        {{-- Auf breiten Viewports laeuft der Verlauf NICHT ueber die
                             volle Bandbreite: 68ch haelt die Zeilenlaenge lesbar.
                             Rechts daneben stehen die Raum-Fakten und der Ausweg in
                             den vollen Client — Information statt Leerraum. --}}
                        <div class="grid gap-4 lg:grid-cols-[minmax(0,68ch)_minmax(0,1fr)] lg:gap-8">
                            {{-- Eingebettete Raum-Ansicht — hinter demselben Gate wie
                                 die Kontaktangabe. canCreateChatRoom allein reicht
                                 NICHT: Es geht ums Mitlesen, nicht ums Anlegen. --}}
                            <div class="min-w-0">
                                @if($this->canSeeChatRoom)
                                    @include('partials.project-chat-feed', [
                                        'roomId' => $projectProposal->nostr_group_h,
                                        'roomName' => $projectProposal->name,
                                        'currentPubkey' => $currentPubkey,
                                        'clientUrl' => $chatClientUrl,
                                    ])
                                @endif
                            </div>

                            <div class="min-w-0 pt-4">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary">
                                    Raum
                                </div>
                                <dl class="mt-2 space-y-2 text-sm">
                                    <div class="flex items-start gap-2">
                                        <dt class="sr-only">Zugang</dt>
                                        <flux:icon name="lock-closed" variant="micro"
                                                   class="mt-0.5 shrink-0 text-text-tertiary" aria-hidden="true"/>
                                        <dd class="text-text-secondary">Nur Vorstand und Einreicher</dd>
                                    </div>
                                    @if($projectProposal->nostr_group_created_at)
                                        <div class="flex items-start gap-2">
                                            <dt class="sr-only">Angelegt am</dt>
                                            <flux:icon name="calendar" variant="micro"
                                                       class="mt-0.5 shrink-0 text-text-tertiary" aria-hidden="true"/>
                                            <dd class="text-text-secondary">
                                                Angelegt am
                                                {{ $projectProposal->nostr_group_created_at->translatedFormat('d.m.Y') }}
                                            </dd>
                                        </div>
                                    @endif
                                </dl>

                                {{-- Harter Full-Load statt wire:navigate: Die Chat-Seite
                                     ist eine eigenstaendige Nostr-Insel; ein SPA-Wechsel
                                     liefert dort einen toten JS-Kontext. Der Link bleibt
                                     auch neben der eingebetteten Ansicht stehen: Die Insel
                                     kann scheitern (SDK, Signer, Relay), und ein toter Chat
                                     ohne Ausweichweg waere schlechter als einer mit. --}}
                                <flux:button
                                    class="mt-3 w-full"
                                    size="sm"
                                    variant="filled"
                                    icon-trailing="arrow-top-right-on-square"
                                    href="{{ $chatClientUrl }}/rooms/{{ $projectProposal->nostr_group_h }}"
                                    target="_blank"
                                >
                                    Chat öffnen
                                </flux:button>
                                <p class="mt-2 text-sm text-text-tertiary">
                                    Öffnet den vollen Chat-Client in einem neuen Tab — auch dann,
                                    wenn die Ansicht hier nicht lädt.
                                </p>
                            </div>
                        </div>
                    @elseif($this->canCreateChatRoom)
                        {{-- projectChatRoom ist in app.js registriert, laeuft also
                             vor Alpines Start. Das Chat-SDK laedt die Komponente
                             selbst per dynamischem Import beim Klick. --}}
                        <div class="max-w-[68ch] pt-4"
                             x-data="projectChatRoom({
                            spaceUrl: @js(config('group.space_url')),
                            roomId: @js($projectProposal->nostrGroupId()),
                            roomName: @js($projectProposal->slug),
                            roomAbout: @js('Antragsraum'),
                            memberPubkeys: @js($this->chatRoomMemberPubkeys),
                            currentPubkey: @js($currentPubkey),
                        })">
                            <p class="text-sm text-text-secondary">
                                Ein privater Raum für die Rückfragen des Vorstands an den
                                Einreicher. Vorstand und Einreicher werden automatisch
                                aufgenommen, sonst sieht ihn niemand.
                            </p>

                            {{-- Die Beschriftung steht fest im Markup und haengt
                                 NICHT an Alpine: Steckte sie in einem x-show-Span,
                                 waere der Knopf unbeschriftet, sobald die Insel
                                 nicht laedt — fuer Screenreader wie fuer Augen. --}}
                            <flux:button
                                class="mt-3 w-full sm:w-auto"
                                size="sm"
                                variant="primary"
                                icon="chat-bubble-left-right"
                                x-on:click="create()"
                                x-bind:disabled="busy"
                            >
                                Chatraum anlegen
                            </flux:button>

                            <p x-show="progress" x-cloak
                               class="mt-2 text-sm text-text-secondary"
                               x-text="progress"></p>

                            <p x-show="error" x-cloak
                               class="mt-2 text-sm text-red-400"
                               x-text="error"></p>
                        </div>
                    @else
                        <p class="max-w-[68ch] pt-4 text-sm text-text-secondary">
                            Der Vorstand legt den Raum an, sobald es Rückfragen zu deinem
                            Antrag gibt. Danach steht er hier.
                        </p>
                    @endif
                </div>
            </div>
        @endif

        <div class="flex flex-col lg:flex-row lg:gap-8 xl:gap-12">
            {{-- Hauptspalte --}}
            <div class="flex-1 min-w-0 order-2 lg:order-1">
                <span class="status-chip status-chip--{{ $this->status->value }}">
                    <flux:icon :name="$this->status->icon()" variant="micro" aria-hidden="true"/>
                    {{ $this->status->label() }}
                </span>

                <h1 class="mt-3 mb-4 max-w-[68ch] text-2xl md:text-[28px] font-bold tracking-tight leading-[1.15] text-text-primary">
                    {{ $projectProposal->name }}
                </h1>

                {{-- Eckdaten --}}
                <div class="mb-6 rounded-xl border border-border-subtle bg-bg-surface p-5">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary">Fördersumme</dt>
                            <dd class="mt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-orange-500">
                                {{ number_format($projectProposal->support_in_sats, 0, ',', '.') }} Sats
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary">Eingereicht am</dt>
                            <dd class="mt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-text-secondary">
                                {{ $projectProposal->created_at->translatedFormat('d.m.Y') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary">Eingereicht von</dt>
                            <dd class="mt-1 flex items-center gap-2 text-sm">
                                <img class="size-6 rounded-full shrink-0"
                                     src="{{ $projectProposal->einundzwanzigPleb->profile?->picture }}"
                                     onerror="this.src='{{ asset('einundzwanzig-alpha.jpg') }}'"
                                     alt="" width="24" height="24" loading="lazy">
                                <a href="https://njump.me/{{ $projectProposal->einundzwanzigPleb->npub }}"
                                   target="_blank" rel="noopener"
                                   class="truncate text-text-secondary hover:text-orange-500 transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">
                                    {{ $projectProposal->einundzwanzigPleb->profile?->name ?? str($projectProposal->einundzwanzigPleb->npub)->limit(24) }}
                                </a>
                            </dd>
                        </div>
                        @if($projectProposal->website)
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary">Webseite</dt>
                                <dd class="mt-1 text-sm">
                                    <a href="{{ $projectProposal->website }}" target="_blank" rel="noopener"
                                       class="break-all text-text-secondary hover:text-orange-500 transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">
                                        {{ str($projectProposal->website)->replaceFirst('https://', '')->limit(40) }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <figure class="mb-6">
                    <img class="w-full max-w-md aspect-square rounded-lg bg-bg-elevated object-cover"
                         src="{{ $projectProposal->getSignedMediaUrl('main', 60, 'preview') }}"
                         alt="" width="448" height="448" loading="lazy" decoding="async">
                </figure>

                <div class="prose dark:prose-invert max-w-[68ch] break-words [&_code]:break-all [&_a]:break-all">
                    {!! $projectProposal->description !!}
                </div>
            </div>

            {{-- Seitenspalte: Vorstand, Abstimmung, Kontakt --}}
            <div class="lg:w-80 xl:w-96 shrink-0 order-1 lg:order-2 space-y-4 mb-6 lg:mb-0 lg:sticky lg:top-6 lg:self-start">

                {{-- Eigene Stimme: steht JEDEM stimmberechtigten Mitglied offen,
                     nicht nur dem Vorstand — deshalb ein neutrales Panel und
                     nicht der orange Vorstands-Rahmen. --}}
                @if($this->isVoter)
                    <div class="rounded-xl border border-border-subtle bg-bg-surface p-5">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary mb-1">
                            Deine Stimme
                        </div>
                        {{-- Beide dürfen abstimmen, nur mit anderem Gewicht — das
                             muss dranstehen, sonst hält ein Mitglied seine Stimme
                             für bindend oder gibt sie gar nicht erst ab. --}}
                        <p class="mb-3 text-sm text-text-tertiary">
                            @if($this->voteCountsTowardsMajority)
                                Zählt zur bindenden Mehrheit des Vorstands.
                            @else
                                Zählt zum Stimmungsbild der Mitglieder, nicht zur Mehrheit.
                            @endif
                        </p>

                        @if($this->ownVote)
                            {{-- Abgegebene Stimmen sind endgültig. Die Seite sagt jetzt
                                 WIE abgestimmt wurde, statt nur DASS abgestimmt wurde. --}}
                            <p @class([
                                'flex items-center gap-2 text-sm',
                                'text-green-500' => (bool) $this->ownVote->value,
                                'text-red-400' => ! (bool) $this->ownVote->value,
                            ])>
                                <flux:icon :name="$this->ownVote->value ? 'check-circle' : 'x-circle'"
                                           variant="micro" aria-hidden="true"/>
                                Du hast {{ $this->ownVote->value ? 'zugestimmt' : 'abgelehnt' }}.
                            </p>
                            <p class="mt-1 text-sm text-text-tertiary">
                                Eine abgegebene Stimme lässt sich nicht mehr ändern.
                            </p>
                        @elseif($this->canVote)
                            <div class="flex gap-2">
                                <flux:modal.trigger name="confirm-approve">
                                    <flux:button variant="primary" icon="hand-thumb-up" class="flex-1 min-h-11">
                                        Zustimmen
                                    </flux:button>
                                </flux:modal.trigger>
                                <flux:modal.trigger name="confirm-reject">
                                    <flux:button variant="danger" icon="hand-thumb-down" class="flex-1 min-h-11">
                                        Ablehnen
                                    </flux:button>
                                </flux:modal.trigger>
                            </div>
                            <p class="mt-2 text-sm text-text-tertiary">
                                Deine Stimme ist endgültig.
                            </p>
                        @endif
                    </div>
                @endif

                {{-- Vorstands-Panel: ausschliesslich fuer den Vorstand. Der orange
                     Rahmen bedeutet "hier gilt anderes Recht" und darf deshalb nicht
                     ueber Aktionen stehen, die jedem Mitglied offenstehen. --}}
                @if($this->canManage)
                    <div class="rounded-xl border border-orange-700 bg-bg-surface overflow-hidden">
                        <div class="flex items-center gap-2 border-b border-orange-700 bg-orange-500/10 px-5 py-3">
                            <flux:icon name="shield-check" variant="micro" class="text-orange-500" aria-hidden="true"/>
                            <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-orange-500">Vorstand</span>
                        </div>

                        <div class="divide-y divide-border-subtle">
                            {{-- (1) Stand --}}
                            <div class="p-5">
                                <div class="flex items-center justify-between gap-2 mb-2">
                                    <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary">Quorum</span>
                                    <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-primary">
                                        {{ $filled }}/{{ $threshold }}
                                    </span>
                                </div>
                                <div class="h-1.5 rounded-full bg-neutral-700 overflow-hidden"
                                     role="img"
                                     aria-label="{{ $filled }} von {{ $threshold }} nötigen Vorstandsstimmen {{ $leading === 'rejected' ? 'gegen' : 'für' }} das Projekt">
                                    <div @class([
                                            'h-full rounded-full',
                                            'bg-red-400' => $leading === 'rejected',
                                            'bg-orange-500' => $leading !== 'rejected',
                                         ])
                                         style="width: {{ $threshold > 0 ? round($filled / $threshold * 100) : 0 }}%"></div>
                                </div>
                                <p class="mt-2 text-sm text-text-secondary">
                                    @if($this->status === ProjectProposalStatus::Supported)
                                        Ausgezahlt — der Antrag ist abgeschlossen.
                                    @elseif($this->status === ProjectProposalStatus::Rejected)
                                        Der Vorstand hat den Antrag abgelehnt.
                                    @elseif($this->status === ProjectProposalStatus::Accepted)
                                        Angenommen. Es fehlt noch die Auszahlung.
                                    @else
                                        @php($missing = $threshold - $filled)
                                        Es {{ $missing === 1 ? 'fehlt' : 'fehlen' }} {{ $missing }}
                                        {{ $missing === 1 ? 'Stimme' : 'Stimmen' }} zur absoluten Mehrheit
                                        von {{ App\Models\ProjectProposal::boardSize() }} Mitgliedern.
                                    @endif
                                </p>
                            </div>

                            {{-- (3) Abschliessen --}}
                            @if($this->canManage)
                                <div class="p-5">
                                    <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary mb-3">
                                        Auszahlung
                                    </div>

                                    @if($projectProposal->sats_paid > 0)
                                        <p class="flex items-center gap-2 text-sm text-green-500">
                                            <flux:icon name="check-circle" variant="micro" aria-hidden="true"/>
                                            {{ number_format($projectProposal->sats_paid, 0, ',', '.') }} Sats erfasst.
                                        </p>
                                        <flux:button wire:click="revertPayout" variant="subtle" size="sm" class="mt-3 min-h-11">
                                            Korrigieren
                                        </flux:button>
                                    @elseif($this->canPayout)
                                        <div class="flex items-end gap-2">
                                            <flux:input type="number" wire:model="payoutSats" label="Sats" class="flex-1"/>
                                            <flux:modal.trigger name="confirm-payout">
                                                <flux:button variant="primary" class="min-h-11">Erfassen</flux:button>
                                            </flux:modal.trigger>
                                        </div>
                                        <flux:error name="payoutSats"/>
                                        <p class="mt-2 text-sm text-text-secondary">Noch nichts ausgezahlt.</p>
                                    @else
                                        {{-- Kein Formular ohne Beschluss: Geld folgt der Mehrheit,
                                             nicht dem Vorstandsstatus des Klickenden. --}}
                                        <p class="flex items-start gap-2 text-sm text-text-secondary">
                                            <flux:icon name="lock-closed" variant="micro" class="mt-0.5 shrink-0 text-text-tertiary" aria-hidden="true"/>
                                            <span>
                                                @if($this->status === ProjectProposalStatus::Rejected)
                                                    Der Antrag wurde vom Vorstand abgelehnt und kann nicht ausgezahlt werden.
                                                @else
                                                    @php($fehlend = App\Models\ProjectProposal::boardVoteThreshold() - $this->boardVotes->where('value', true)->count())
                                                    Auszahlung erst nach Beschluss: Es
                                                    {{ $fehlend === 1 ? 'fehlt' : 'fehlen' }} noch {{ $fehlend }}
                                                    {{ $fehlend === 1 ? 'Zustimmung' : 'Zustimmungen' }} zur absoluten Mehrheit.
                                                @endif
                                            </span>
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Vorstandsentscheidung: die Stimmen, die zur Mehrheit zählen --}}
                <div class="rounded-xl border border-border-subtle bg-bg-surface p-5">
                    <div class="flex items-baseline justify-between gap-2 mb-3">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary">
                            Vorstandsentscheidung
                        </span>
                        <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary">
                            {{ $filled }}/{{ $threshold }}
                        </span>
                    </div>
                    <x-vote-tally label="Zustimmung" :votes="$approvals" tone="approve"/>
                    <x-vote-tally label="Ablehnung" :votes="$rejections" tone="reject"/>
                    <p class="mt-3 text-sm text-text-tertiary">
                        Bindend ist die absolute Mehrheit von
                        {{ App\Models\ProjectProposal::boardSize() }} Vorstandsmitgliedern.
                    </p>
                </div>

                {{-- Stimmungsbild: bewusst IMMER sichtbar, auch ohne Stimmen. Vorher
                     hing der Block an otherVotes->isNotEmpty() und verschwand
                     komplett — das las sich, als könnten Mitglieder nicht abstimmen. --}}
                <div class="rounded-xl border border-border-subtle bg-bg-surface p-5">
                    <div class="flex items-baseline justify-between gap-2 mb-1">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary">
                            Stimmungsbild der Mitglieder
                        </span>
                        <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary">
                            {{ $this->otherVotes->count() }}
                        </span>
                    </div>
                    <p class="mb-3 text-sm text-text-tertiary">
                        Zählt nicht zur Mehrheit — es zeigt dem Vorstand, wie der Verein zum Antrag steht.
                    </p>

                    @if($this->otherVotes->isNotEmpty())
                        <x-vote-tally label="Zustimmung" :votes="$otherApprovals" tone="approve"/>
                        <x-vote-tally label="Ablehnung" :votes="$otherRejections" tone="reject"/>
                    @else
                        <p class="text-sm text-text-secondary">
                            Noch keine Stimme abgegeben.
                        </p>
                    @endif
                </div>

                {{-- Kontakt: nur Vorstand und Einreicher --}}
                @if($this->canSeeContact)
                    <div class="rounded-xl border border-border-subtle bg-bg-surface p-5">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary mb-3">
                            Kontakt zum Einreicher
                        </div>
                        @if($projectProposal->contact_via_nostr_dm)
                            <div class="flex items-start gap-2">
                                <flux:icon name="envelope" variant="micro" class="mt-1 shrink-0 text-text-tertiary" aria-hidden="true"/>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-text-secondary">Nostr-DM erwünscht</p>
                                    <x-copy-value
                                        class="mt-1"
                                        :value="$projectProposal->einundzwanzigPleb->npub"
                                        label="npub"
                                    />
                                </div>
                            </div>
                        @elseif($projectProposal->contact_alternative)
                            <div class="flex items-start gap-2">
                                <flux:icon name="envelope" variant="micro" class="mt-1 shrink-0 text-text-tertiary" aria-hidden="true"/>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-text-secondary">Bevorzugter Kanal</p>
                                    <x-copy-value
                                        class="mt-1"
                                        :value="$projectProposal->contact_alternative"
                                        label="Kontaktangabe"
                                    />
                                </div>
                            </div>
                        @else
                            <div class="flex items-start gap-2">
                                <flux:icon name="exclamation-triangle" variant="micro" class="mt-1 shrink-0 text-yellow-400" aria-hidden="true"/>
                                <div>
                                    <p class="text-sm text-text-primary">Kein Kontaktweg hinterlegt.</p>
                                    <p class="mt-1 text-sm text-text-secondary">
                                        Der Einreicher hat Direktnachrichten abgewählt und keinen anderen Kanal genannt.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

            </div>
        </div>

        {{-- Stimmabgabe bestaetigen: sie ist endgueltig, also darf sie kein
             Fehlklick sein. --}}
        @if($this->canVote)
            <flux:modal name="confirm-approve" class="min-w-88">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Dem Projekt zustimmen?</flux:heading>
                        <flux:text class="mt-2">
                            <p>Du stimmst „{{ $projectProposal->name }}" zu.</p>
                            <p>Deine Stimme ist endgültig und kann nicht zurückgenommen werden.</p>
                        </flux:text>
                    </div>
                    <div class="flex gap-2">
                        <flux:spacer/>
                        <flux:modal.close>
                            <flux:button variant="ghost">Abbrechen</flux:button>
                        </flux:modal.close>
                        <flux:button wire:click="handleApprove" variant="primary" icon="hand-thumb-up">
                            Zustimmen
                        </flux:button>
                    </div>
                </div>
            </flux:modal>

            <flux:modal name="confirm-reject" class="min-w-88">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Projekt ablehnen?</flux:heading>
                        <flux:text class="mt-2">
                            <p>Du lehnst „{{ $projectProposal->name }}" ab.</p>
                            <p>Deine Stimme ist endgültig und kann nicht zurückgenommen werden.</p>
                        </flux:text>
                    </div>
                    <div class="flex gap-2">
                        <flux:spacer/>
                        <flux:modal.close>
                            <flux:button variant="ghost">Abbrechen</flux:button>
                        </flux:modal.close>
                        <flux:button wire:click="handleNotApprove" variant="danger" icon="hand-thumb-down">
                            Ablehnen
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        @endif

        {{-- Auszahlung bestaetigen: der einzige nicht triviale Schritt --}}
        @if($this->canManage)
            <flux:modal name="confirm-payout" class="min-w-88">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Auszahlung erfassen?</flux:heading>
                        <flux:text class="mt-2">
                            <p>{{ number_format((int) $payoutSats, 0, ',', '.') }} Sats als ausgezahlt erfassen?</p>
                            <p>Das Projekt gilt danach als unterstützt.</p>
                        </flux:text>
                    </div>
                    <div class="flex gap-2">
                        <flux:spacer/>
                        <flux:modal.close>
                            <flux:button variant="ghost">Abbrechen</flux:button>
                        </flux:modal.close>
                        <flux:button wire:click="recordPayout" variant="primary">Auszahlung erfassen</flux:button>
                    </div>
                </div>
            </flux:modal>
        @endif
    </div>

    {{-- Die Chat-Insel NUR hier laden, nicht in app.js: Der Import von
         @einundzwanzig/group hat Seiteneffekte (welshman-Singletons, eine
         AUTH-Policy fuer Relays, localStorage und IndexedDB) und zieht einen
         eigenen ~950-KB-Chunk. Beides gehoert nicht auf jede Vereinsseite. --}}
    {{-- Die Alpine-Komponente ist in app.js registriert; das Chat-SDK laedt sie
         selbst per dynamischem Import beim Klick. Hier bleibt nur die
         Space-Adresse: Das Package liest window.__nostrSpace beim Laden und
         faellt sonst auf ws://localhost:3334 zurueck — im Betrieb also auf gar
         nichts. Es setzt das sonst in seinem eigenen head-Partial, das wir
         bewusst nicht einbinden. --}}
    @if($this->canSeeChatRoom || $this->canCreateChatRoom)
        @push('head')
            <script>window.__nostrSpace = @js(config('group.space_url'))</script>
        @endpush
    @endif
</div>
