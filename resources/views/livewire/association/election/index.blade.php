<?php

use App\Models\EinundzwanzigPleb;
use App\Models\Election;
use App\Support\NostrAuth;
use Livewire\Component;

new class extends Component {

    public bool $isAllowed = false;

    public ?string $currentPubkey = null;

    public ?EinundzwanzigPleb $currentPleb = null;

    public array $elections = [];

    protected $listeners = [
        'nostrLoggedOut' => 'handleNostrLoggedOut',
        'nostrLoggedIn' => 'handleNostrLoggedIn',
    ];

    public function mount(): void
    {
        $this->elections = Election::query()
            ->get()
            ->toArray();
        if (NostrAuth::check()) {
            $this->currentPubkey = NostrAuth::pubkey();
            $logPubkeys = [
                '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033',
                '430169631f2f0682c60cebb4f902d68f0c71c498fd1711fd982f052cf1fd4279',
            ];
            if (in_array($this->currentPubkey, $logPubkeys, true)) {
                $this->isAllowed = true;
            }
        }
    }

    public function handleNostrLoggedOut(): void
    {
        $this->isAllowed = false;
        $this->currentPubkey = null;
        $this->currentPleb = null;
    }

    public function handleNostrLoggedIn($pubkey): void
    {
        NostrAuth::login($pubkey);
        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()
            ->where('pubkey', $pubkey)->first();
        $logPubkeys = [
            '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033',
            '430169631f2f0682c60cebb4f902d68f0c71c498fd1711fd982f052cf1fd4279',
        ];
        if (in_array($this->currentPubkey, $logPubkeys, true)) {
            $this->isAllowed = true;
        }
    }

    public function saveElection($index): void
    {
        $election = $this->elections[$index];
        $electionModel = Election::find($election['id']);
        $electionModel->candidates = $election['candidates'];
        $electionModel->save();
    }
};
?>

<div>
    @if($isAllowed)
        <div class="relative flex h-full">
             @foreach($elections as $election)
                <div class="w-full sm:w-1/3 p-4" wire:key="election-{{ $loop->index }}">
                    <div class="shadow-lg rounded-lg overflow-hidden">
                        {{ $election['year'] }}
                    </div>
                    <div class="shadow-lg rounded-lg overflow-hidden">
                        <flux:field>
                            <flux:label>Kandidaten</flux:label>
                            <flux:textarea wire:model="elections.{{ $loop->index }}.candidates" rows="25" placeholder="Kandidaten..."/>
                            <flux:error name="elections.{{ $loop->index }}.candidates" />
                        </flux:field>
                    </div>
                    <div class="py-2">
                        <flux:button wire:click="saveElection({{ $loop->index }})" wire:loading.attr="disabled">
                            Speichern
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <flux:callout variant="warning" icon="exclamation-circle">
                <flux:heading>Einstellungen können nicht bearbeitet werden</flux:heading>
                <p>
                    Zugriff auf die Wahl-Einstellungen ist nur für spezielle autorisierte Benutzer möglich.
                </p>
                <p class="mt-3">
                    @if(!NostrAuth::check())
                        Bitte melde dich zunächst mit Nostr an.
                    @else
                        Dein Benutzer-Account ist nicht für diese Funktion autorisiert. Bitte kontaktiere den Vorstand, wenn du Zugriff benötigst.
                    @endif
                </p>
            </flux:callout>
        </div>
    @endif
</div>
