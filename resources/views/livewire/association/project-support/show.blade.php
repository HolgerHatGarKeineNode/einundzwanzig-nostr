<?php

use App\Livewire\Forms\VoteForm;
use App\Models\Vote;
use App\Support\NostrAuth;
use Livewire\Component;

new class extends Component {
    public $projectProposal;

    public bool $isAllowed = false;

    public ?string $currentPubkey = null;

    public ?object $currentPleb = null;

    public bool $ownVoteExists = false;

    public function mount($projectProposal): void
    {
        $this->projectProposal = \App\Models\ProjectProposal::query()->where('slug', $projectProposal)->firstOrFail();
        if (NostrAuth::check()) {
            $this->currentPubkey = NostrAuth::pubkey();
            $this->isAllowed = true;
        }
    }

    public function getBoardVotesProperty()
    {
        return Vote::query()
            ->where('project_proposal_id', $this->projectProposal->id)
            ->whereHas('einundzwanzigPleb', fn($q) => $q->whereIn('npub', config('einundzwanzig.config.current_board')))
            ->get();
    }

    public function getOtherVotesProperty()
    {
        return Vote::query()
            ->where('project_proposal_id', $this->projectProposal->id)
            ->whereDoesntHave(
                'einundzwanzigPleb',
                fn($q) => $q->whereIn('npub', config('einundzwanzig.config.current_board'))
            )
            ->get();
    }

    public function handleApprove(): void
    {
        Vote::query()->updateOrCreate([
            'project_proposal_id' => $this->projectProposal->id,
            'einundzwanzig_pleb_id' => $this->currentPleb->id,
        ], [
            'value' => true,
        ]);
    }

    public function handleNotApprove(): void
    {
        Vote::query()->updateOrCreate([
            'project_proposal_id' => $this->projectProposal->id,
            'einundzwanzig_pleb_id' => $this->currentPleb->id,
        ], [
            'value' => false,
        ]);
    }
}
?>

<div>
    @if($projectProposal->accepted || $isAllowed)
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full">
            <div class="mx-auto flex flex-col lg:flex-row lg:space-x-8 xl:space-x-12">
                <div class="flex-1">
                    <div class="mb-6">
                        <flux:button :href="route('association.projectSupport')" variant="primary" size="sm" icon="chevron-left">
                            Zurück zur Übersicht
                        </flux:button>
                    </div>
                    <div class="text-sm font-semibold text-violet-500 uppercase mb-2">
                        {{ $projectProposal->created_at->translatedFormat('d.m.Y') }}
                    </div>
                    <header class="mb-4">
                        <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold mb-2">
                            {{ $projectProposal->name }}
                        </h1>
                        <x-markdown>
                            {!! $projectProposal->description !!}
                        </x-markdown>
                    </header>

                    <div class="space-y-3 sm:flex sm:items-center sm:justify-between sm:space-y-0 mb-6">
                        <div class="flex items-center sm:mr-4">
                            <a class="block mr-2 shrink-0" href="#0">
                                <img class="rounded-full"
                                     src="{{ $projectProposal->einundzwanzigPleb->profile?->picture ?? asset('einundzwanzig-alpha.jpg') }}"
                                     width="32" height="32" alt="User">
                            </a>
                            <div class="text-sm whitespace-nowrap">Eingereicht von
                                <div class="font-semibold text-gray-800 dark:text-gray-100">
                                    {{ $projectProposal->einundzwanzigPleb?->profile->name ?? str($projectProposal->einundzwanzigPleb->npub)->limit(32) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center sm:justify-end space-x-2">
                            <div
                                class="text-xs inline-flex items-center font-medium border border-gray-200 dark:border-gray-700/60 text-gray-600 dark:text-gray-400 rounded-full text-center px-2.5 py-1">
                                <a target="_blank" href="{{ $projectProposal->website }}">Webseite</a>
                            </div>
                            <div
                                class="text-xs inline-flex font-medium uppercase bg-green-500/20 text-green-700 rounded-full text-center px-2.5 py-1">
                                {{ number_format($projectProposal->support_in_sats, 0, ',', '.') }} Sats
                            </div>
                        </div>
                    </div>

                    <figure class="mb-6">
                        <img class="rounded-sm h-48" src="{{ $projectProposal->getFirstMediaUrl('main') }}"
                             alt="Picture">
                    </figure>

                    <hr class="my-6 border-t border-gray-100 dark:border-gray-700/60">
                </div>

                @if($isAllowed && !$projectProposal->accepted)
                    <div class="lg:w-80 xl:w-96 shrink-0 space-y-4">
                        <div class="bg-white dark:bg-gray-800 p-5 shadow-sm rounded-xl">
                            @if(!$ownVoteExists)
                                <div class="space-y-2">
                                    <flux:button wire:click="handleApprove" class="w-full">
                                        <i class="fill-current shrink-0 fa-sharp-duotone fa-solid fa-thumbs-up mr-2"></i>
                                        Zustimmen
                                    </flux:button>
                                    <flux:button wire:click="handleNotApprove" variant="danger" class="w-full">
                                        <i class="fill-current shrink-0 fa-sharp-duotone fa-solid fa-thumbs-down mr-2"></i>
                                        Ablehnen
                                    </flux:button>
                                </div>
                            @else
                                <p class="text-sm text-gray-700 dark:text-gray-300">Du hast bereits abgestimmt.</p>
                            @endif
                        </div>

                        <div class="bg-white dark:bg-gray-800 p-5 shadow-sm rounded-xl">
                            <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2">
                                Zustimmungen des Vorstands ({{ count($this->boardVotes->where('value', 1)) }})
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 p-5 shadow-sm rounded-xl">
                            <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2">
                                Ablehnungen des Vorstands ({{ count($this->boardVotes->where('value', 0)) }})
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 p-5 shadow-sm rounded-xl">
                            <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2">
                                Zustimmungen der übrigen Mitglieder
                                ({{ count($this->otherVotes->where('value', 1)) }})
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 p-5 shadow-sm rounded-xl">
                            <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2">
                                Ablehnungen der übrigen Mitglieder
                                ({{ count($this->otherVotes->where('value', 0)) }})
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full mx-auto">
            <flux:callout variant="warning" icon="exclamation-circle">
                <flux:heading>Zugriff auf Projektförderung nicht möglich</flux:heading>
                <p>
                    @if(!NostrAuth::check())
                        Bitte melde dich zunächst mit Nostr an, um Zugriff auf die Projektförderung zu erhalten.
                    @else
                        Du benötigst eine gültige Nostr-Authentifizierung, um diese Projektförderung einzusehen.
                    @endif
                </p>
            </flux:callout>
        </div>
    @endif
</div>
