<?php

use App\Enums\AssociationStatus;
use App\Models\EinundzwanzigPleb;
use App\Support\NostrAuth;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public bool $isAllowed = false;

    public ?string $currentPubkey = null;

    public ?EinundzwanzigPleb $currentPleb = null;

    public string $sortBy = 'association_status';

    public string $sortDirection = 'desc';

    public ?int $selectedPlebId = null;

    public ?int $confirmAcceptId = null;

    public ?int $confirmDeleteId = null;

    public string $search = '';

    public bool $showPaidOnly = false;

    public $plebs = [];

    public function updatedSearch(): void
    {
        $this->plebs = $this->loadPlebs();
    }

    protected $listeners = [
        'nostrLoggedOut' => 'handleNostrLoggedOut',
        'nostrLoggedIn' => 'handleNostrLoggedIn',
    ];

    public function mount(): void
    {
        if (NostrAuth::check()) {
            $this->currentPubkey = NostrAuth::pubkey();
            $this->currentPleb = \App\Models\EinundzwanzigPleb::query()
                ->where('pubkey', $this->currentPubkey)->first();
            $allowedPubkeys = [
                '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033',
                '430169631f2f0682c60cebb4f902d68f0c71c498fd1711fd982f052cf1fd4279',
                '7acf30cf60b85c62b8f654556cc21e4016df8f5604b3b6892794f88bb80d7a1d',
                'f240be2b684f85cc81566f2081386af81d7427ea86250c8bde6b7a8500c761ba',
                '19e358b8011f5f4fc653c565c6d4c2f33f32661f4f90982c9eedc292a8774ec3',
                'acbcec475a1a4f9481939ecfbd1c3d111f5b5a474a39ae039bbc720fdd305bec',
            ];
            if (in_array($this->currentPubkey, $allowedPubkeys, true)) {
                $this->isAllowed = true;
            }
        }

        $this->plebs = $this->loadPlebs();
    }

    private function loadPlebs()
    {
        $query = EinundzwanzigPleb::query()
            ->with([
                'profile',
                'paymentEvents' => fn ($query) => $query
                    ->where('year', date('Y'))
                    ->where('paid', true),
            ]);

        if ($this->search) {
            $query->where(function ($query) {
                $query->whereHas('profile', function ($query) {
                    $query->where('name', 'ilike', '%'.$this->search.'%');
                })->orWhere('npub', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->showPaidOnly) {
            $query->whereHas('paymentEvents', fn ($query) => $query
                ->where('year', date('Y'))
                ->where('paid', true)
            );
        }

        return $query->tap(fn ($query) => $this->sortBy ? $query->orderBy($this->sortBy, $this->sortDirection) : $query)
            ->get();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->plebs = $this->loadPlebs();
    }

    public function togglePaidFilter(): void
    {
        $this->showPaidOnly = !$this->showPaidOnly;
        $this->plebs = $this->loadPlebs();
    }

    public function openPaymentModal(int $plebId): void
    {
        $this->selectedPlebId = $plebId;
        Flux::modal('payment-details')->show();
    }

    public function accept($rowId): void
    {
        $this->confirmAcceptId = $rowId;
        Flux::modal('confirm-accept-pleb')->show();
    }

    public function delete($rowId): void
    {
        $this->confirmDeleteId = $rowId;
        Flux::modal('confirm-delete-pleb')->show();
    }

    public function acceptPleb(): void
    {
        if ($this->confirmAcceptId) {
            $pleb = EinundzwanzigPleb::query()->findOrFail($this->confirmAcceptId);
            $for = $pleb->application_for;
            $text = $pleb->application_text;
            $pleb->association_status = AssociationStatus::from($for);
            $pleb->application_for = null;
            $pleb->archived_application_text = $text;
            $pleb->application_text = null;
            $pleb->save();

            $this->confirmAcceptId = null;
            Flux::modal('confirm-accept-pleb')->close();
        }
    }

    public function deletePleb(): void
    {
        if ($this->confirmDeleteId) {
            $pleb = EinundzwanzigPleb::query()->findOrFail($this->confirmDeleteId);
            $pleb->application_for = null;
            $pleb->application_text = null;
            $pleb->save();

            $this->confirmDeleteId = null;
            Flux::modal('confirm-delete-pleb')->close();
        }
    }

    public function closePaymentModal(): void
    {
        $this->selectedPlebId = null;
        Flux::modal('payment-details')->close();
    }

    #[Computed]
    public function selectedPleb(): ?EinundzwanzigPleb
    {
        return EinundzwanzigPleb::with(['paymentEvents'])->find($this->selectedPlebId);
    }
};
?>

<div>
    @if($isAllowed)
        <div class="mb-4 flex gap-2">
            <flux:input
                    wire:model.live.debounce.300ms="search"
                placeholder="Suche nach Name oder Npub..."
                icon="magnifying-glass"
                class="flex-1"
            />
            <flux:button
                wire:click="togglePaidFilter"
                :variant="$showPaidOnly ? 'primary' : 'ghost'"
                icon="check"
            >
                {{ $showPaidOnly ? 'Alle anzeigen' : 'Nur Bezahlt' }}
            </flux:button>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Avatar</flux:table.column>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'name'"
                    :direction="$sortDirection"
                    wire:click="sort('name')"
                >
                    Name
                </flux:table.column>
                <flux:table.column
                    sortable
                    :sorted="$sortBy === 'association_status'"
                    :direction="$sortDirection"
                    wire:click="sort('association_status')"
                >
                    Aktueller Status
                </flux:table.column>
                <flux:table.column>Beitrag {{ date('Y') }}</flux:table.column>
                <flux:table.column>Aktionen</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->plebs as $pleb)
                    <flux:table.row :key="$pleb->id">
                        <flux:table.cell>
                            <flux:avatar
                                size="xl"
                                :src="$pleb->profile?->picture ?? asset('einundzwanzig-alpha.jpg')"
                                :name="$pleb->profile?->name ?? ''"
                                rounded
                            />
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex flex-col">
                                <flux:heading size="sm">
                                    {{ $pleb->profile?->name ?: $pleb->profile?->display_name ?? '' }}
                                </flux:heading>
                                <a
                                    target="_blank"
                                    href="https://nostrudel.ninja/u/{{ $pleb->npub }}"
                                    class="text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
                                >
                                    Nostr Profile
                                </a>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge
                                size="sm"
                                :color="match($pleb->association_status) {
                                        AssociationStatus::DEFAULT => 'zinc',
                                        AssociationStatus::PASSIVE => 'yellow',
                                        AssociationStatus::ACTIVE => 'green',
                                        AssociationStatus::HONORARY => 'blue',
                                        default => 'red',
                                    }"
                                inset="top bottom"
                            >
                                {{ $pleb->association_status->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($pleb->paymentEvents->count() > 0 && $pleb->paymentEvents->first()->paid)
                                <flux:button
                                    wire:click="openPaymentModal({{ $pleb->id }})"
                                    variant="ghost"
                                    size="sm"
                                    icon="banknotes"
                                >
                                        <span class="text-green-500">
                                            {{ number_format($pleb->paymentEvents->first()->amount, 0, ',', '.') }}
                                        </span>
                                </flux:button>
                            @else
                                <flux:text class="text-zinc-500">keine Zahlung vorhanden</flux:text>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            <div class="flex gap-2">
                                <flux:button
                                    wire:click="delete({{ $pleb->id }})"
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    inset="top bottom"
                                ></flux:button>

                                @if($pleb->application_for)
                                    <flux:button
                                        wire:click="accept({{ $pleb->id }})"
                                        variant="ghost"
                                        size="sm"
                                        icon="check"
                                        inset="top bottom"
                                    ></flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full mx-auto">
            <flux:callout variant="warning" icon="exclamation-circle">
                <flux:heading>Mitglieder können nicht bearbeitet werden</flux:heading>
                <p>
                    Zugriff auf die Mitgliederverwaltung ist nur für spezielle autorisierte Benutzer möglich.
                </p>
                <p class="mt-3">
                    @if(!NostrAuth::check())
                        Bitte melde dich zunächst mit Nostr an.
                    @else
                        Dein Benutzer-Account ist nicht für diese Funktion autorisiert. Bitte kontaktiere den Vorstand,
                        wenn du Zugriff benötigst.
                    @endif
                </p>
            </flux:callout>
        </div>
    @endif

    <!-- Payment Details Modal -->
    <flux:modal name="payment-details" class="max-w-full">
        @if($this->selectedPleb)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Zahlungsdetails</flux:heading>
                    <flux:subheading class="mt-2">
                        {{ $this->selectedPleb->profile?->name ?: $this->selectedPleb->profile?->display_name ?? 'Unbekannt' }}
                    </flux:subheading>
                </div>

                @if($this->selectedPleb->application_text)
                    <flux:callout icon="information-circle" variant="info">
                        {{ $this->selectedPleb->application_text }}
                    </flux:callout>
                @endif

                <section>
                    <flux:heading size="md" class="mb-4">Bisherige Zahlungen</flux:heading>

                    @if($this->selectedPleb->paymentEvents->count() > 0)
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Satoshis</flux:table.column>
                                <flux:table.column>Jahr</flux:table.column>
                                <flux:table.column>Event-ID</flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach($this->selectedPleb->paymentEvents as $payment)
                                    <flux:table.row :key="$payment->id">
                                        <flux:table.cell variant="strong">{{ $payment->amount }}</flux:table.cell>
                                        <flux:table.cell>{{ $payment->year }}</flux:table.cell>
                                        <flux:table.cell class="text-xs">{{ $payment->event_id }}</flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @else
                        <flux:callout variant="info" icon="information-circle">
                            Keine Zahlungen gefunden
                        </flux:callout>
                    @endif
                </section>

                <div class="flex gap-2">
                    <flux:spacer/>
                    <flux:button wire:click="closePaymentModal" variant="primary">Schließen</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    <!-- Accept Confirmation Modal -->
    <flux:modal name="confirm-accept-pleb" class="min-w-88">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Mitglied akzeptieren</flux:heading>
                <flux:subheading class="mt-2">
                    Bist du sicher, dass du dieses Mitglied akzeptieren möchtest?
                </flux:subheading>
            </div>
            <div class="flex gap-2">
                <flux:spacer/>
                <flux:modal.close>
                    <flux:button variant="ghost">Abbrechen</flux:button>
                </flux:modal.close>
                <flux:button wire:click="acceptPleb" variant="primary">Ja, akzeptieren</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal name="confirm-delete-pleb" class="min-w-88">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Bewerbung ablehnen</flux:heading>
                <flux:subheading class="mt-2">
                    Bist du sicher, dass du diese Bewerbung ablehnen möchtest?
                </flux:subheading>
            </div>
            <div class="flex gap-2">
                <flux:spacer/>
                <flux:modal.close>
                    <flux:button variant="ghost">Abbrechen</flux:button>
                </flux:modal.close>
                <flux:button wire:click="deletePleb" variant="danger">Ja, ablehnen</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
