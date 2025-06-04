<?php

use Livewire\Volt\Component;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Key\Key;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;
use WireUi\Actions\Notification;

use function Laravel\Folio\{middleware, name};
use function Livewire\Volt\{state, mount, on, computed};

name('association.projectSupport');

state([
    'search' => '',
    'activeFilter' => 'all',
    'projects' => fn()
        => \App\Models\ProjectProposal::query()
        ->with([
            'einundzwanzigPleb.profile',
            'votes',
        ])
        ->orderBy('created_at', 'desc')
        ->get(),
    'isAllowed' => false,
    'currentPubkey' => null,
    'currentPleb' => null,
]);

$projects = computed(function () {
    return $this->projects
        ->when($this->search, function ($collection) {
            return $collection->filter(function ($project) {
                return str_contains(strtolower($project->name), strtolower($this->search)) ||
                    str_contains(strtolower($project->description), strtolower($this->search)) ||
                    str_contains(strtolower($project->einundzwanzigPleb->profile->name), strtolower($this->search));
            });
        });
});

on([
    'nostrLoggedIn' => function ($pubkey) {
        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $pubkey)->first();
        $this->isAllowed = true;
    },
    'nostrLoggedOut' => function () {
        $this->isAllowed = false;
        $this->currentPubkey = null;
        $this->currentPleb = null;
    },
]);

$confirmDelete = function ($id) {
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
};

$delete = function ($id) {
    \App\Models\ProjectProposal::query()->findOrFail($id)->delete();
    $this->projects = \App\Models\ProjectProposal::query()
        ->with([
            'einundzwanzigPleb.profile',
            'votes',
        ])
        ->orderBy('created_at', 'desc')
        ->get();
};

?>

<x-layouts.app
    :seo="new \RalphJSmit\Laravel\SEO\Support\SEOData(title: 'Projekt Unterstützungen', description: 'Einundzwanzig Projektunterstützungen')"
>
    @volt
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
                                 placeholder="Suche…"/>
                    </form>

                    <!-- Add meetup button -->
                    @if($currentPleb && $currentPleb->association_status->value > 1)
                        <x-button :href="route('association.projectSupport.create')" icon="plus"
                                  label="Projekt einreichen"/>
                    @endif
                </div>

            </div>

            <!-- Filters -->
            <div class="mb-5">
                <ul class="flex flex-wrap -m-1">
                    <li class="m-1">
                        <button wire:click="$set('activeFilter', 'all')"
                                class="inline-flex items-center justify-center text-sm font-medium leading-5 rounded-full px-3 py-1 border {{ $activeFilter === 'all' ? 'border-transparent shadow-sm bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-800' : 'border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400' }} transition">
                            Alle
                        </button>
                    </li>
                    <li class="m-1">
                        <button wire:click="$set('activeFilter', 'new')"
                                class="inline-flex items-center justify-center text-sm font-medium leading-5 rounded-full px-3 py-1 border {{ $activeFilter === 'new' ? 'border-transparent shadow-sm bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-800' : 'border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400' }} transition">
                            Neu
                        </button>
                    </li>
                    <li class="m-1">
                        <button wire:click="$set('activeFilter', 'supported')"
                                class="inline-flex items-center justify-center text-sm font-medium leading-5 rounded-full px-3 py-1 border {{ $activeFilter === 'supported' ? 'border-transparent shadow-sm bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-800' : 'border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400' }} transition">
                            Unterstützt
                        </button>
                    </li>
                    <li class="m-1">
                        <button wire:click="$set('activeFilter', 'rejected')"
                                class="inline-flex items-center justify-center text-sm font-medium leading-5 rounded-full px-3 py-1 border {{ $activeFilter === 'rejected' ? 'border-transparent shadow-sm bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-800' : 'border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400' }} transition">
                            Abgelehnt
                        </button>
                    </li>
                </ul>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 italic mb-4">{{ $this->projects->count() }}Projekte
            </div>

            <!-- Content -->
            <div class="grid xl:grid-cols-2 gap-6 mb-8">
                @foreach($this->projects as $project)
                    <x-project-card :project="$project" :currentPleb="$currentPleb" :section="$activeFilter"/>
                @endforeach
            </div>

        </div>
    </div>
    @endvolt
</x-layouts.app>
