<?php

namespace App\Livewire\Association\ProjectSupport;

use App\Models\ProjectProposal;
use App\Support\NostrAuth;
use Livewire\Component;
use WireUi\Actions\Notification;

final class Index extends Component
{
    public string $activeFilter = 'all';

    public string $search = '';

    public \Illuminate\Database\Eloquent\Collection $projects;

    public bool $isAllowed = false;

    public ?string $currentPubkey = null;

    public ?\App\Models\EinundzwanzigPleb $currentPleb = null;

    protected $listeners = [
        'nostrLoggedIn' => 'handleNostrLoggedIn',
        'nostrLoggedOut' => 'handleNostrLoggedOut',
    ];

    public function mount(): void
    {
        $this->loadProjects();
        if (NostrAuth::check()) {
            $this->currentPubkey = NostrAuth::pubkey();
            $this->currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $this->currentPubkey)->first();
            $this->isAllowed = true;
        }
    }

    public function updatedSearch(): void
    {
        $this->loadProjects();
    }

    public function loadProjects(): void
    {
        $this->projects = ProjectProposal::query()
            ->with([
                'einundzwanzigPleb.profile',
                'votes',
            ])
            ->where(function ($query) {
                $query
                    ->where('name', 'ilike', '%'.$this->search.'%')
                    ->orWhere('description', 'ilike', '%'.$this->search.'%')
                    ->orWhereHas('einundzwanzigPleb.profile', function ($q) {
                        $q->where('name', 'ilike', '%'.$this->search.'%');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function handleNostrLoggedIn($pubkey): void
    {
        NostrAuth::login($pubkey);
        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $pubkey)->first();
        $this->isAllowed = true;
    }

    public function handleNostrLoggedOut(): void
    {
        $this->isAllowed = false;
        $this->currentPubkey = null;
        $this->currentPleb = null;
    }

    public function confirmDelete($id): void
    {
        $notification = new Notification($this);
        $notification->confirm([
            'title' => 'Projektunterstützung löschen',
            'message' => 'Bist du sicher, dass du diese Projektunterstützung löschen möchtest?',
            'accept' => [
                'label' => 'Ja, löschen',
                'method' => 'delete',
                'params' => $id,
            ],
        ]);
    }

    public function setFilter($filter): void
    {
        $this->activeFilter = $filter;
    }

    public function delete($id): void
    {
        ProjectProposal::query()->findOrFail($id)->delete();
        $this->loadProjects();
    }
}
