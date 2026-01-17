<?php

namespace App\Livewire\Association\ProjectSupport;

use App\Livewire\Forms\VoteForm;
use App\Models\ProjectProposal;
use App\Models\Vote;
use App\Support\NostrAuth;
use Livewire\Component;

final class Show extends Component
{
    public VoteForm $form;

    public ?ProjectProposal $projectProposal = null;

    public bool $isAllowed = false;

    public ?string $currentPubkey = null;

    public ?\App\Models\EinundzwanzigPleb $currentPleb = null;

    public bool $ownVoteExists = false;

    public \Illuminate\Database\Eloquent\Collection $boardVotes;

    public \Illuminate\Database\Eloquent\Collection $otherVotes;

    protected $listeners = [
        'nostrLoggedIn' => 'handleNostrLoggedIn',
        'nostrLoggedOut' => 'handleNostrLoggedOut',
    ];

    public function mount(ProjectProposal $projectProposal): void
    {
        $this->projectProposal = $projectProposal;
        if (NostrAuth::check()) {
            $this->currentPubkey = NostrAuth::pubkey();
            $this->handleNostrLoggedIn($this->currentPubkey);
        }
        $this->boardVotes = $this->getBoardVotes();
        $this->otherVotes = $this->getOtherVotes();
    }

    public function handleNostrLoggedIn($pubkey): void
    {
        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $pubkey)->first();
        $this->isAllowed = true;
        $this->ownVoteExists = Vote::query()
            ->where('project_proposal_id', $this->projectProposal->id)
            ->where('einundzwanzig_pleb_id', $this->currentPleb->id)
            ->exists();
    }

    public function handleNostrLoggedOut(): void
    {
        $this->isAllowed = false;
        $this->currentPubkey = null;
        $this->currentPleb = null;
    }

    public function getBoardVotes(): \Illuminate\Database\Eloquent\Collection
    {
        return Vote::query()
            ->where('project_proposal_id', $this->projectProposal->id)
            ->whereHas('einundzwanzigPleb', fn ($q) => $q->whereIn('npub', config('einundzwanzig.config.current_board')))
            ->get();
    }

    public function getOtherVotes(): \Illuminate\Database\Eloquent\Collection
    {
        return Vote::query()
            ->where('project_proposal_id', $this->projectProposal->id)
            ->whereDoesntHave(
                'einundzwanzigPleb',
                fn ($q) => $q->whereIn('npub', config('einundzwanzig.config.current_board'))
            )
            ->get();
    }

    public function approve(): void
    {
        Vote::query()->updateOrCreate([
            'project_proposal_id' => $this->projectProposal->id,
            'einundzwanzig_pleb_id' => $this->currentPleb->id,
        ], [
            'value' => true,
        ]);
        $this->form->reset();
        $this->ownVoteExists = true;
        $this->boardVotes = $this->getBoardVotes();
        $this->otherVotes = $this->getOtherVotes();
    }

    public function notApprove(): void
    {
        $this->form->validate();

        Vote::query()->updateOrCreate([
            'project_proposal_id' => $this->projectProposal->id,
            'einundzwanzig_pleb_id' => $this->currentPleb->id,
        ], [
            'value' => false,
        ]);
        $this->form->reset();
        $this->ownVoteExists = true;
    }
}
