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
     * Trägt die Auszahlung ein. Nur Vorstand — die Berechtigung wird hier
     * serverseitig geprüft, weil jede öffentliche Livewire-Methode direkt
     * aufrufbar ist, unabhängig davon, was die View rendert.
     */
    public function recordPayout(): void
    {
        Gate::forUser(NostrAuth::user())->authorize('accept', $this->projectProposal);

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

                {{-- Vorstands-Panel: alle Vorstandsaktionen an EINEM Ort --}}
                @if($this->isVoter || $this->canManage)
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

                            {{-- (2) Handeln --}}
                            @if($this->isVoter)
                                <div class="p-5">
                                    <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary mb-3">
                                        Deine Stimme
                                    </div>

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
                                    @else
                                        <div class="flex items-end gap-2">
                                            <flux:input type="number" wire:model="payoutSats" label="Sats" class="flex-1"/>
                                            <flux:modal.trigger name="confirm-payout">
                                                <flux:button variant="primary" class="min-h-11">Erfassen</flux:button>
                                            </flux:modal.trigger>
                                        </div>
                                        <flux:error name="payoutSats"/>
                                        <p class="mt-2 text-sm text-text-secondary">Noch nichts ausgezahlt.</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Abstimmung: verdichtet statt vier leerer Kaesten --}}
                <div class="rounded-xl border border-border-subtle bg-bg-surface p-5">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary mb-3">
                        Vorstand
                    </div>
                    <x-vote-tally label="Zustimmung" :votes="$approvals" tone="approve"/>
                    <x-vote-tally label="Ablehnung" :votes="$rejections" tone="reject"/>

                    @if($this->otherVotes->isNotEmpty())
                        <hr class="my-4 border-t border-border-subtle">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-text-tertiary mb-3">
                            Übrige Mitglieder
                        </div>
                        <x-vote-tally label="Zustimmung" :votes="$otherApprovals" tone="approve"/>
                        <x-vote-tally label="Ablehnung" :votes="$otherRejections" tone="reject"/>
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
                                <div class="min-w-0">
                                    <p class="text-sm text-text-secondary">Nostr-DM erwünscht</p>
                                    <p class="mt-1 break-all text-[11px] font-semibold tracking-[0.08em] text-text-primary">
                                        {{ $projectProposal->einundzwanzigPleb->npub }}
                                    </p>
                                </div>
                            </div>
                        @elseif($projectProposal->contact_alternative)
                            <div class="flex items-start gap-2">
                                <flux:icon name="envelope" variant="micro" class="mt-1 shrink-0 text-text-tertiary" aria-hidden="true"/>
                                <div class="min-w-0">
                                    <p class="text-sm text-text-secondary">Bevorzugter Kanal</p>
                                    <p class="mt-1 break-all text-[11px] font-semibold tracking-[0.08em] text-text-primary">
                                        {{ $projectProposal->contact_alternative }}
                                    </p>
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
</div>
