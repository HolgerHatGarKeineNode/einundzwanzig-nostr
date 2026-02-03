<?php

use App\Livewire\Traits\WithNostrAuth;
use App\Models\ProjectProposal;
use App\Models\Vote;
use App\Support\NostrAuth;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    use WithNostrAuth;

    #[Locked]
    public $projectProposal;

    #[Locked]
    public bool $isAllowed = false;

    #[Locked]
    public ?string $currentPubkey = null;

    #[Locked]
    public ?object $currentPleb = null;

    #[Locked]
    public bool $ownVoteExists = false;

    public function mount($projectProposal): void
    {
        $this->projectProposal = ProjectProposal::query()->where('slug', $projectProposal)->firstOrFail();
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
        $this->ownVoteExists = Vote::query()
            ->where('project_proposal_id', $this->projectProposal->id)
            ->where('einundzwanzig_pleb_id', $this->currentPleb->id)
            ->exists();
    }

    public function handleNotApprove(): void
    {
        Vote::query()->updateOrCreate([
            'project_proposal_id' => $this->projectProposal->id,
            'einundzwanzig_pleb_id' => $this->currentPleb->id,
        ], [
            'value' => false,
        ]);
        $this->ownVoteExists = Vote::query()
            ->where('project_proposal_id', $this->projectProposal->id)
            ->where('einundzwanzig_pleb_id', $this->currentPleb->id)
            ->exists();
    }
}
?>

<div>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full">
        <div class="mx-auto flex flex-col lg:flex-row lg:space-x-8 xl:space-x-12">
            <div class="flex-1">
                <div class="mb-6">
                    <flux:button :href="route('association.projectSupport')" variant="primary" size="sm"
                                 icon="chevron-left">
                        Zurück zur Übersicht
                    </flux:button>
                </div>
                <div class="text-sm font-semibold text-violet-500 uppercase mb-2">
                    {{ $projectProposal->created_at->translatedFormat('d.m.Y') }}
                </div>
                <header class="mb-4">
                    <h1 class="text-2xl md:text-3xl text-zinc-800 dark:text-zinc-100 font-bold mb-2">
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
                            <div class="font-semibold text-zinc-800 dark:text-zinc-100">
                                {{ $projectProposal->einundzwanzigPleb?->profile->name ?? str($projectProposal->einundzwanzigPleb->npub)->limit(32) }}
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center sm:justify-end space-x-2">
                        <div
                            class="text-xs inline-flex items-center font-medium border border-zinc-200 dark:border-zinc-700/60 text-zinc-600 dark:text-zinc-400 rounded-full text-center px-2.5 py-1">
                            <a target="_blank" href="{{ $projectProposal->website }}">Webseite</a>
                        </div>
                        <div
                            class="text-xs inline-flex font-medium uppercase bg-green-500/20 text-green-700 rounded-full text-center px-2.5 py-1">
                            {{ number_format($projectProposal->support_in_sats, 0, ',', '.') }} Sats
                        </div>
                    </div>
                </div>

                <figure class="mb-6">
                    <img class="rounded-sm h-48" src="{{ $projectProposal->getSignedMediaUrl('main') }}"
                         alt="Picture">
                </figure>

                <hr class="my-6 border-t border-zinc-100 dark:border-zinc-700/60">
            </div>

            <div class="lg:w-80 xl:w-96 shrink-0 space-y-4">
                <div class="bg-white dark:bg-zinc-800 p-5 shadow-sm rounded-xl">
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
                        <p class="text-sm text-zinc-700 dark:text-zinc-300">Du hast bereits abgestimmt.</p>
                    @endif
                </div>

                <div class="bg-white dark:bg-zinc-800 p-5 shadow-sm rounded-xl">
                    <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 mb-2">
                        Zustimmungen des Vorstands ({{ count($this->boardVotes->where('value', 1)) }})
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-800 p-5 shadow-sm rounded-xl">
                    <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 mb-2">
                        Ablehnungen des Vorstands ({{ count($this->boardVotes->where('value', 0)) }})
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-800 p-5 shadow-sm rounded-xl">
                    <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 mb-2">
                        Zustimmungen der übrigen Mitglieder
                        ({{ count($this->otherVotes->where('value', 1)) }})
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-800 p-5 shadow-sm rounded-xl">
                    <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 mb-2">
                        Ablehnungen der übrigen Mitglieder
                        ({{ count($this->otherVotes->where('value', 0)) }})
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
