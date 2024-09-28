<?php

use Livewire\Volt\Component;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Key\Key;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;
use function Livewire\Volt\with;
use function Laravel\Folio\{middleware};
use function Laravel\Folio\name;
use function Livewire\Volt\{on};

name('meetups.grid');

?>


<x-layouts.app title="{{ __('Meetups') }}">
    @volt
    <div class="relative flex">

        <!-- Profile sidebar -->
        <div
            id="profile-sidebar"
            class="absolute z-20 top-0 bottom-0 w-full md:w-auto md:static md:top-auto md:bottom-auto -mr-px md:translate-x-0 transition-transform duration-200 ease-in-out"
            :class="profileSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div
                class="sticky top-16 bg-white dark:bg-[#1B1B1B] overflow-x-hidden overflow-y-auto no-scrollbar shrink-0 border-r border-gray-200 dark:border-gray-700/60 md:w-[18rem] xl:w-[20rem] h-[calc(100dvh-64px)]">

                <!-- Profile group -->
                <div>
                    <!-- Group header -->
                    <div class="sticky top-0 z-10">
                        <div
                            class="flex items-center bg-white dark:bg-[#1B1B1B] border-b border-gray-200 dark:border-gray-700/60 px-5 h-16">
                            <div class="w-full flex items-center justify-between">
                                <!-- Profile image -->
                                <div class="relative">
                                    <div class="grow flex items-center truncate">
                                        <div class="truncate">
                                            <span
                                                class="font-semibold text-gray-800 dark:text-gray-100">All meetups</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Add button -->
                                <button
                                    class="p-1.5 shrink-0 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 shadow-sm ml-2">
                                    <svg class="fill-current text-violet-500" width="16" height="16"
                                         viewBox="0 0 16 16">
                                        <path
                                            d="M15 7H9V1c0-.6-.4-1-1-1S7 .4 7 1v6H1c-.6 0-1 .4-1 1s.4 1 1 1h6v6c0 .6.4 1 1 1s1-.4 1-1V9h6c.6 0 1-.4 1-1s-.4-1-1-1Z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Group body -->
                    <div class="px-5 py-4">
                        <!-- Search form -->
                        <div class="relative">
                            <label for="profile-search" class="sr-only">Search</label>
                            <input id="profile-search" class="form-input w-full pl-9 bg-white dark:bg-gray-800 rounded"
                                   type="search" placeholder="Search…"/>
                            <button class="absolute inset-0 right-auto group cursor-default" aria-label="Search">
                                <svg
                                    class="shrink-0 fill-current text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-400 ml-3 mr-2"
                                    width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M7 14c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7zM7 2C4.243 2 2 4.243 2 7s2.243 5 5 5 5-2.243 5-5-2.243-5-5-5z"/>
                                    <path
                                        d="M15.707 14.293L13.314 11.9a8.019 8.019 0 01-1.414 1.414l2.393 2.393a.997.997 0 001.414 0 .999.999 0 000-1.414z"/>
                                </svg>
                            </button>
                        </div>
                        <!-- Team members -->
                        <div class="mt-4">
                            <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase mb-3">
                                Countries
                            </div>
                            <ul class="mb-6">
                                <li class="-mx-2">
                                    <button
                                        class="w-full p-2 rounded-lg bg-[linear-gradient(135deg,var(--tw-gradient-stops))] from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]"
                                        @click="profileSidebarOpen = false">
                                        <div class="flex items-center">
                                            <div class="relative mr-2">
                                                <img class="w-8 h-8 rounded-full"
                                                     src="{{ asset('vendor/blade-country-flags/1x1-de.svg') }}"
                                                     width="32" height="32" alt="User 08"/>
                                            </div>
                                            <div class="truncate">
                                                <span class="text-sm font-medium text-gray-800 dark:text-gray-100">Deutschland</span>
                                            </div>
                                        </div>
                                    </button>
                                </li>
                                <li class="-mx-2">
                                    <button
                                        class="w-full p-2"
                                        @click="profileSidebarOpen = false">
                                        <div class="flex items-center">
                                            <div class="relative mr-2">
                                                <img class="w-8 h-8 rounded-full"
                                                     src="{{ asset('vendor/blade-country-flags/1x1-at.svg') }}"
                                                     width="32" height="32" alt="User 08"/>
                                            </div>
                                            <div class="truncate">
                                                <span class="text-sm font-medium text-gray-800 dark:text-gray-100">Österreich</span>
                                            </div>
                                        </div>
                                    </button>
                                </li>
                                <li class="-mx-2">
                                    <button
                                        class="w-full p-2"
                                        @click="profileSidebarOpen = false">
                                        <div class="flex items-center">
                                            <div class="relative mr-2">
                                                <img class="w-8 h-8 rounded-full"
                                                     src="{{ asset('vendor/blade-country-flags/1x1-ch.svg') }}"
                                                     width="32" height="32" alt="User 08"/>
                                            </div>
                                            <div class="truncate">
                                                <span class="text-sm font-medium text-gray-800 dark:text-gray-100">Schweiz</span>
                                            </div>
                                        </div>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Profile body -->
        <div
            class="grow flex flex-col md:translate-x-0 transition-transform duration-300 ease-in-out"
            :class="profileSidebarOpen ? 'translate-x-1/3' : 'translate-x-0'"
        >

            <!-- Profile background -->
            <div class="relative h-56 bg-gray-200 dark:bg-gray-900">
                <img class="object-cover object-top h-full w-full" src="{{ asset('img/meetup_saarland.jpg') }}"
                     width="979" height="220"
                     alt="Profile background"/>
                <!-- Close button -->
                <button
                    class="md:hidden absolute top-4 left-4 sm:left-6 text-white opacity-80 hover:opacity-100"
                    @click.stop="profileSidebarOpen = !profileSidebarOpen"
                    aria-controls="profile-sidebar"
                    :aria-expanded="profileSidebarOpen"
                >
                    <span class="sr-only">Close sidebar</span>
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.7 18.7l1.4-1.4L7.8 13H20v-2H7.8l4.3-4.3-1.4-1.4L4 12z"/>
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="relative px-4 sm:px-6 pb-8">

                <!-- Pre-header -->
                <div class="-mt-16 mb-6 sm:mb-3">

                    <div class="flex flex-col items-center sm:flex-row sm:justify-between sm:items-end">

                        <!-- Avatar -->
                        <div class="inline-flex -ml-1 -mt-1 mb-4 sm:mb-0" style="height: 128px">
                        </div>

                        <!-- Actions -->
                        <div class="flex space-x-2 sm:mb-2">
                            {{-- ACTIONS --}}
                        </div>

                    </div>

                </div>

                <div class="grid xl:grid-cols-2 gap-6 mb-8">

                    <!-- Item 1 -->
                    <article class="flex bg-white dark:bg-[#1B1B1B] shadow-sm rounded-xl overflow-hidden">
                        <!-- Image -->
                        <a class="relative block w-24 sm:w-56 xl:sidebar-expanded:w-40 2xl:sidebar-expanded:w-56 shrink-0" href="meetups-post.html">
                            <img class="absolute object-cover object-center w-full h-full" src="./images/meetups-thumb-01.jpg" width="220" height="236" alt="Meetup 01" />
                            <!-- Like button -->
                            <button class="absolute top-0 right-0 mt-4 mr-4">
                                <div class="text-gray-100 bg-gray-900 bg-opacity-60 rounded-full">
                                    <span class="sr-only">Like</span>
                                    <svg class="h-8 w-8 fill-current" viewBox="0 0 32 32">
                                        <path d="M22.682 11.318A4.485 4.485 0 0019.5 10a4.377 4.377 0 00-3.5 1.707A4.383 4.383 0 0012.5 10a4.5 4.5 0 00-3.182 7.682L16 24l6.682-6.318a4.5 4.5 0 000-6.364zm-1.4 4.933L16 21.247l-5.285-5A2.5 2.5 0 0112.5 12c1.437 0 2.312.681 3.5 2.625C17.187 12.681 18.062 12 19.5 12a2.5 2.5 0 011.785 4.251h-.003z" />
                                    </svg>
                                </div>
                            </button>
                        </a>
                        <!-- Content -->
                        <div class="grow p-5 flex flex-col">
                            <div class="grow">
                                <div class="text-sm font-semibold text-violet-500 uppercase mb-2">Mon 27 Dec, 2024</div>
                                <a class="inline-flex mb-2" href="meetups-post.html">
                                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Silicon Valley Bootstrapper Breakfast Online for 2024</h3>
                                </a>
                                <div class="text-sm">Lorem ipsum is placeholder text commonly used in the graphic, print, and publishing industries for previewing layouts.</div>
                            </div>
                            <!-- Footer -->
                            <div class="flex justify-between items-center mt-3">
                                <!-- Tag -->
                                <div class="text-xs inline-flex items-center font-medium border border-gray-200 dark:border-gray-700/60 text-gray-600 dark:text-gray-400 rounded-full text-center px-2.5 py-1">
                                    <svg class="w-4 h-3 fill-gray-400 dark:fill-gray-500 mr-2" viewBox="0 0 16 12">
                                        <path d="m16 2-4 2.4V2a2 2 0 0 0-2-2H2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7.6l4 2.4V2ZM2 10V2h8v8H2Z" />
                                    </svg>
                                    <span>Online Event</span>
                                </div>
                                <!-- Avatars -->
                                <div class="flex items-center space-x-2">
                                    <div class="flex -space-x-3 -ml-0.5">
                                        <img class="rounded-full border-2 border-white dark:border-gray-800 box-content" src="./images/avatar-01.jpg" width="28" height="28" alt="User 01" />
                                        <img class="rounded-full border-2 border-white dark:border-gray-800 box-content" src="./images/avatar-04.jpg" width="28" height="28" alt="User 04" />
                                        <img class="rounded-full border-2 border-white dark:border-gray-800 box-content" src="./images/avatar-05.jpg" width="28" height="28" alt="User 05" />
                                    </div>
                                    <div class="text-xs font-medium text-gray-400 dark:text-gray-500 italic">+22</div>
                                </div>
                            </div>
                        </div>
                    </article>

                </div>

            </div>

        </div>

    </div>
    @endvolt
</x-layouts.app>
