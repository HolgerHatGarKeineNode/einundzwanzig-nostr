<?php

use Livewire\Volt\Component;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Key\Key;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

use function Laravel\Folio\{middleware};
use function Laravel\Folio\name;
use function Livewire\Volt\{state, mount, on, computed};

name('association.projectSupport');

state([
    'search' => '',
    'projects' => fn()
        => \App\Models\ProjectProposal::query()
        ->with([
            'einundzwanzigPleb.profile',
            'votes',
        ])
        ->get(),
]);

?>

<x-layouts.app title="Projekt Unterstützungen">
    @volt
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
                <x-button :href="route('association.projectSupport.create')" icon="plus"
                          label="Projekt einreichen"/>
            </div>

        </div>

        <!-- Filters -->
        {{--<div class="mb-5">
            <ul class="flex flex-wrap -m-1">
                <li class="m-1">
                    <button class="inline-flex items-center justify-center text-sm font-medium leading-5 rounded-full px-3 py-1 border border-transparent shadow-sm bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-800 transition">View All</button>
                </li>
                <li class="m-1">
                    <button class="inline-flex items-center justify-center text-sm font-medium leading-5 rounded-full px-3 py-1 border border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 transition">Online</button>
                </li>
                <li class="m-1">
                    <button class="inline-flex items-center justify-center text-sm font-medium leading-5 rounded-full px-3 py-1 border border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 transition">Local</button>
                </li>
                <li class="m-1">
                    <button class="inline-flex items-center justify-center text-sm font-medium leading-5 rounded-full px-3 py-1 border border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 transition">This Week</button>
                </li>
                <li class="m-1">
                    <button class="inline-flex items-center justify-center text-sm font-medium leading-5 rounded-full px-3 py-1 border border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 transition">This Month</button>
                </li>
                <li class="m-1">
                    <button class="inline-flex items-center justify-center text-sm font-medium leading-5 rounded-full px-3 py-1 border border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 transition">Following</button>
                </li>
            </ul>
        </div>--}}
        <div class="text-sm text-gray-500 dark:text-gray-400 italic mb-4">{{ count($projects) }} Projekte</div>

        <!-- Content -->
        <div class="grid xl:grid-cols-2 gap-6 mb-8">

            @foreach($projects as $project)
                <article
                    wire:key="project_{{ $project->id }}"
                    class="flex bg-white dark:bg-gray-800 shadow-sm rounded-xl overflow-hidden">
                    <!-- Image -->
                    <a class="relative block w-24 sm:w-56 xl:sidebar-expanded:w-40 2xl:sidebar-expanded:w-56 shrink-0"
                       href="meetups-post.html">
                        <img class="absolute object-cover object-center w-full h-full"
                             src="{{ asset('einundzwanzig-alpha.jpg') }}" alt="Meetup 01">
                        <button class="absolute top-0 right-0 mt-4 mr-4">
                            <img class="rounded-full h-8 w-8" src="{{ $project->einundzwanzigPleb->profile->picture }}"
                                 alt="">
                        </button>
                    </a>
                    <!-- Content -->
                    <div class="grow p-5 flex flex-col">
                        <div class="grow">
                            <div class="text-sm font-semibold text-amber-500 uppercase mb-2">
                                Eingereicht von: {{ $project->einundzwanzigPleb->profile->name }}
                            </div>
                            <a class="inline-flex mb-2">
                                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">
                                    {{ $project->name }}
                                </h3>
                            </a>
                            <div class="text-sm line-clamp-6">
                                {!! strip_tags($project->description) !!}
                            </div>
                        </div>
                        <!-- Footer -->
                        <div class="flex justify-between items-center mt-3">
                            <!-- Tag -->
                            <div
                                class="text-xs inline-flex items-center font-bold border border-gray-200 dark:border-gray-700/60 text-gray-600 dark:text-gray-200 rounded-full text-center px-2.5 py-1">
                                <span>{{ number_format($project->support_in_sats, 0, ',', '.') }} Sats</span>
                            </div>
                            <!-- Avatars -->
                            @if($project->votes->count() > 0)
                                <div class="flex items-center space-x-2">
                                    <div class="text-xs font-medium text-gray-400 dark:text-gray-300 italic">
                                        Anzahl der Unterstützer: +{{ $project->votes->count() }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach

        </div>

    </div>
    @endvolt
</x-layouts.app>
