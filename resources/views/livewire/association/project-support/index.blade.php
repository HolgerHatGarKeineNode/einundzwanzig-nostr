<?php

use App\Models\EinundzwanzigPleb;
use App\Models\ProjectProposal;
use App\Support\NostrAuth;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

new class extends Component {
    public string $activeFilter = 'all';

    public ?string $confirmDeleteId = null;

    public string $search = '';

    public Collection $projects;

    public bool $isAllowed = false;

    public ?string $currentPubkey = null;

    public ?EinundzwanzigPleb $currentPleb = null;

    public ?ProjectProposal $projectToDelete = null;

    protected $listeners = [
        'nostrLoggedIn' => 'handleNostrLoggedIn',
        'nostrLoggedOut' => 'handleNostrLoggedOut',
        'confirmDeleteProject' => 'confirmDeleteProject',
    ];

    public function mount(): void
    {
        $this->loadProjects();
        if (NostrAuth::check()) {
            $this->currentPubkey = NostrAuth::pubkey();
            $this->currentPleb = EinundzwanzigPleb::query()->where('pubkey', $this->currentPubkey)->first();
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

    public function confirmDeleteProject($id): void
    {
        $this->projectToDelete = ProjectProposal::query()->findOrFail($id);
        Flux::modal('delete-project')->show();
    }

    public function setFilter($filter): void
    {
        $this->activeFilter = $filter;
    }

    public function delete(): void
    {
        if ($this->projectToDelete) {
            $this->projectToDelete->delete();
            Flux::toast('Projektunterstützung gelöscht.');
            $this->loadProjects();
            Flux::modals()->close();
            $this->projectToDelete = null;
        }
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
                    <flux:input type="search" wire:model.live.debounce="search"
                                placeholder="Suche" icon="magnifying-glass"/>
                </form>

                <!-- Add meetup button -->
                @if($currentPleb && $currentPleb->association_status->value > 1 && $currentPleb->paymentEvents()->where('year', date('Y'))->where('paid', true)->exists())
                    <flux:button :href="route('association.projectSupport.create')" icon="plus" variant="primary">
                        Projekt einreichen
                    </flux:button>
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

         <!-- Delete confirmation modal -->
         <flux:modal name="delete-project" class="min-w-88">
             <div class="space-y-6">
                 <div>
                     <flux:heading size="lg">Projektunterstützung löschen?</flux:heading>
                     <flux:text class="mt-2">
                         <p>Du bist dabei, diese Projektunterstützung zu löschen.</p>
                         <p>Diese Aktion kann nicht rückgängig gemacht werden.</p>
                     </flux:text>
                 </div>
                 <div class="flex gap-2">
                     <flux:spacer/>
                     <flux:modal.close>
                         <flux:button variant="ghost">Abbrechen</flux:button>
                     </flux:modal.close>
                     <flux:button wire:click="delete" variant="danger">Löschen</flux:button>
                 </div>
             </div>
         </flux:modal>
     </div>
</div>
