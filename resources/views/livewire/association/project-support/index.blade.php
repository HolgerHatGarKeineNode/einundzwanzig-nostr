<?php

use App\Models\ProjectProposal;
use App\Support\NostrAuth;
use Livewire\Component;
use WireUi\Actions\Notification;

new class extends Component {
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

    public function render()
    {
        return view('livewire.association.project-support')
            ->layout('layouts.app')
            ->with([
                'seo' => new \RalphJSmit\Laravel\SEO\Support\SEOData(title: 'Projekt Unterstützungen', description: 'Einundzwanzig Projektunterstützungen')
            ]);
    }
};
?>

<div>
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

            <!-- Page header -->
            <div class="sm:flex sm:justify-between sm:items-center mb-5">

                <!-- Left: Title -->
                <div class="mb-4 sm:mb-0">
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">
                        Einundzwanzig Projektunterstützungen
                    </h1>
                </div>

                <!-- Right: Actions -->
                <div class="grid grid-cols-1 sm:grid-cols-2 justify-start sm:justify-end gap-2">

                    <!-- Search form -->
                    <form class="relative">
                        <x-input type="search" wire:model.live.debounce="search"
                                 placeholder="Suche"/>
                    </form>

                    <!-- Add meetup button -->
                    @if($currentPleb && $currentPleb->association_status->value > 1 && $currentPleb->paymentEvents()->where('year', date('Y'))->where('paid', true)->exists())
                        <x-button :href="route('association.projectSupport.create')" icon="plus"
                                  label="Projekt einreichen"/>
                    @endif
                </div>

            </div>

            <!-- Filters -->
            <div class="mb-5">
                <ul class="flex flex-wrap -m-1">
                    <li class="m-1">
                        <button wire:click="setFilter('all')"
                                class="inline-flex items-center justify-center text-sm font-medium leading-5 rounded-full px-3 py-1 border @if($activeFilter === 'all') border-transparent shadow-sm bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-800 @else border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 @endif transition">
                            Alle
                        </button>
                    </li>
                    <li class="m-1">
                        <button wire:click="setFilter('new')"
                                class="inline-flex items-center justify-center text-sm font-medium leading-5 rounded-full px-3 py-1 border @if($activeFilter === 'new') border-transparent shadow-sm bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-800 @else border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 @endif transition">
                            Neu
                        </button>
                    </li>
                    <li class="m-1">
                        <button wire:click="setFilter('supported')"
                                class="inline-flex items-center justify-center text-sm font-medium leading-5 rounded-full px-3 py-1 border @if($activeFilter === 'supported') border-transparent shadow-sm bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-800 @else border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 @endif transition">
                            Unterstützt
                        </button>
                    </li>
                    <li class="m-1">
                        <button wire:click="setFilter('rejected')"
                                class="inline-flex items-center justify-center text-sm font-medium leading-5 rounded-full px-3 py-1 border @if($activeFilter === 'rejected') border-transparent shadow-sm bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-800 @else border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 @endif transition">
                            Abgelehnt
                        </button>
                    </li>
                </ul>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 italic mb-4">{{ $projects->count() }} Projekte</div>

            <!-- Content -->
            <div class="grid xl:grid-cols-2 gap-6 mb-8">
                @foreach($this->projects as $project)
                    <x-project-card :project="$project" :currentPleb="$currentPleb" :section="$activeFilter"/>
                @endforeach
            </div>

        </div>
    </div>
