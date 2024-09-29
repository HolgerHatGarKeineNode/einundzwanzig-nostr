<?php

use Livewire\Volt\Component;

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;
use function Livewire\Volt\with;
use function Laravel\Folio\{middleware};
use function Laravel\Folio\name;
use function Livewire\Volt\{on, form};

name('association.profile');

state(['currentPubkey' => null]);
state(['currentPleb' => null]);

form(\App\Livewire\Forms\ApplicationForm::class);

on([
    'nostrLoggedIn' => function ($pubkey) {
        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $pubkey)->first();
    },
]);

$save = function ($type) {
    $this->form->validate();
    $this->currentPleb
        ->update([
            'application_for' => $type,
            'application_text' => $this->form->reason,
        ]);
};

?>

<x-layouts.app title="{{ __('Wahl') }}">
    @volt
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

        <!-- Page header -->
        <div class="mb-8">

            <!-- Title -->
            <h1 class="text-2xl md:text-3xl text-[#1B1B1B] dark:text-gray-100 font-bold">
                Einundzwanzig ist, was du draus machst
            </h1>

        </div>

        <div class="bg-white dark:bg-[#1B1B1B] shadow-sm rounded-xl mb-8">
            <div class="flex flex-col md:flex-row md:-mr-px">

                <!-- Sidebar -->
                <div
                    class="flex flex-nowrap overflow-x-scroll no-scrollbar md:block md:overflow-auto px-3 py-6 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700/60 min-w-60 md:space-y-3">
                    <!-- Group 1 -->
                    <div>
                        <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase mb-3">
                            Meine Mitgliedschaft
                        </div>
                        <ul class="flex flex-nowrap md:block mr-3 md:mr-0">
                            <li class="mr-0.5 md:mr-0 md:mb-0.5">
                                <a class="flex items-center px-2.5 py-2 rounded-lg whitespace-nowrap bg-[linear-gradient(135deg,var(--tw-gradient-stops))] from-orange-500/[0.12] dark:from-orange-500/[0.24] to-orange-500/[0.04]"
                                   href="#0">
                                    <i class="fa-sharp-duotone fa-solid fa-id-card-clip shrink-0 fill-current text-orange-400 mr-2"></i>
                                    <span
                                        class="text-sm font-medium text-orange-500 dark:text-orange-400">Status</span>
                                </a>
                            </li>
                            {{--<li class="mr-0.5 md:mr-0 md:mb-0.5">
                                <a class="flex items-center px-2.5 py-2 rounded-lg whitespace-nowrap"
                                   href="notifications.html">
                                    <svg class="shrink-0 fill-current text-gray-400 dark:text-gray-500 mr-2" width="16"
                                         height="16" viewBox="0 0 16 16">
                                        <path
                                            d="m9 12.614 4.806 1.374a.15.15 0 0 0 .174-.21L8.133 2.082a.15.15 0 0 0-.268 0L2.02 13.777a.149.149 0 0 0 .174.21L7 12.614V9a1 1 0 1 1 2 0v3.614Zm-1 1.794-5.257 1.503c-1.798.514-3.35-1.355-2.513-3.028L6.076 1.188c.791-1.584 3.052-1.584 3.845 0l5.848 11.695c.836 1.672-.714 3.54-2.512 3.028L8 14.408Z"/>
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-200">My Notifications</span>
                                </a>
                            </li>--}}
                        </ul>
                    </div>
                    <!-- Group 2 -->
                    {{--<div>
                        <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase mb-3">Experience
                        </div>
                        <ul class="flex flex-nowrap md:block mr-3 md:mr-0">
                            <li class="mr-0.5 md:mr-0 md:mb-0.5">
                                <a class="flex items-center px-2.5 py-2 rounded-lg whitespace-nowrap"
                                   href="feedback.html">
                                    <svg class="shrink-0 fill-current text-gray-400 dark:text-gray-500 mr-2" width="16"
                                         height="16" viewBox="0 0 16 16">
                                        <path
                                            d="M14.3.3c.4-.4 1-.4 1.4 0 .4.4.4 1 0 1.4l-8 8c-.2.2-.4.3-.7.3-.3 0-.5-.1-.7-.3-.4-.4-.4-1 0-1.4l8-8zM15 7c.6 0 1 .4 1 1 0 4.4-3.6 8-8 8s-8-3.6-8-8 3.6-8 8-8c.6 0 1 .4 1 1s-.4 1-1 1C4.7 2 2 4.7 2 8s2.7 6 6 6 6-2.7 6-6c0-.6.4-1 1-1z"/>
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-700 dark:hover:text-gray-200">Give Feedback</span>
                                </a>
                            </li>
                        </ul>
                    </div>--}}
                </div>

                <!-- Panel -->
                <div class="grow">

                    <!-- Panel body -->
                    <div class="p-6 space-y-6">
                        <h2 class="text-2xl text-[#1B1B1B] dark:text-gray-100 font-bold mb-5">Aktueller Status</h2>

                        <!-- Picture -->
                        <section>
                            <div class="flex items-center justify-between">
                                <x-button label="Mit Nostr verbinden" @click="openNostrLogin"
                                          x-show="!$store.nostr.user"/>
                                <template x-if="$store.nostr.user">
                                    <div class="flex items">
                                        <img class="w-12 h-12 rounded-full"
                                             x-bind:src="$store.nostr.user.picture"
                                             alt="">
                                        <div class="ml-4">
                                            <h3 class="text-lg leading-snug text-[#1B1B1B] dark:text-gray-100 font-bold"
                                                x-text="$store.nostr.user.nip05"></h3>
                                            <div class="text-sm text-gray-500 dark:text-gray-400"
                                                 x-text="$store.nostr.user.nip05"></div>
                                        </div>
                                    </div>
                                </template>
                                @if($currentPubkey)
                                    <div
                                        class="inline-flex min-w-80 px-4 py-2 rounded-lg text-sm bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700/60 text-gray-600 dark:text-gray-100">
                                        <div class="flex w-full justify-between items-start">
                                            <div class="flex">
                                                <svg class="shrink-0 fill-current text-green-500 mt-[3px] mr-3"
                                                     width="16" height="16" viewBox="0 0 16 16">
                                                    <path
                                                        d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zM7 11.4L3.6 8 5 6.6l2 2 4-4L12.4 6 7 11.4z"></path>
                                                </svg>
                                                <div>Profil in der Datenbank vorhanden. Bewerbung kann erfolgen.</div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </section>

                        <!-- Business Profile -->
                        <section>
                            @if($currentPubkey && !$currentPleb->application_for && $currentPleb->association_status->value < 2)
                                <h3 class="text-xl leading-snug text-[#1B1B1B] dark:text-gray-100 font-bold mb-1">
                                    passives Mitglied werden
                                </h3>
                                <div class="text-sm">
                                    <x-textarea
                                        corner="Beschreibe deine Motivation, passives Mitglied zu werden."
                                        label="Warum möchtest du passives Mitglied werden?" wire:model="form.reason"/>
                                </div>
                                <div class="sm:flex sm:items-center space-y-4 sm:space-y-0 sm:space-x-4 mt-5">
                                    <div class="sm:w-1/3 flex flex-col space-y-2">
                                        <x-button label="Für passive Mitgliedschaft bewerben"
                                                  wire:click="save({{ \App\Enums\AssociationStatus::PASSIVE() }})"/>
                                        <x-badge outline
                                                 label="Es wird im Anschluss ein Nostr Event erzeugt, das du mit dem Mitgliedsbeitrag zappen kannst, nachdem du bestätigt wurdest."/>
                                    </div>
                                </div>
                            @endif
                        </section>

                        <!-- Email -->
                        <section>
                            @if($currentPubkey && !$currentPleb->application_for && $currentPleb->association_status->value < 2)
                                <h3 class="text-xl leading-snug text-[#1B1B1B] dark:text-gray-100 font-bold mb-1">
                                    aktives Mitglied werden
                                </h3>
                                <div class="text-sm">
                                    <x-textarea
                                        corner="Woher kennen wir dich? Was möchtest du einbringen?"
                                        description="Wir bitten dich mindestens von mind. 3 aktiven Mitgliedern auf Nostr gefolgt zu werden."
                                        label="Warum möchtest du aktives Mitglied werden?" wire:model="form.reason"/>
                                </div>
                                <div class="sm:flex sm:items-center space-y-4 sm:space-y-0 sm:space-x-4 mt-5">
                                    <div class="sm:w-1/3 flex flex-col space-y-2">
                                        <x-button label="Für aktive Mitgliedschaft bewerben"
                                                  wire:click="save({{ \App\Enums\AssociationStatus::ACTIVE() }})"/>
                                        <x-badge outline
                                                 label="Es wird im Anschluss ein Nostr Event erzeugt, das du mit dem Mitgliedsbeitrag zappen kannst, nachdem du bestätigt wurdest."/>
                                    </div>
                                </div>
                            @endif
                        </section>

                        <section>
                            @if($currentPubkey && $currentPleb->application_for)
                                <div class="inline-flex flex-col w-full max-w-lg px-4 py-2 rounded-lg text-sm bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700/60 text-gray-600 dark:text-gray-400">
                                    <div class="flex w-full justify-between items-start">
                                        <div class="flex">
                                            <svg class="shrink-0 fill-current text-yellow-500 mt-[3px] mr-3" width="16" height="16" viewBox="0 0 16 16">
                                                <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 12c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1zm1-3H7V4h2v5z"></path>
                                            </svg>
                                            <div>
                                                <div class="font-medium text-gray-800 dark:text-gray-100 mb-1">Du hast dich erfolgreich mit folgendem Grund beworben:</div>
                                                <div>{{ $currentPleb->application_text }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </section>

                        <section>
                            @if($currentPleb && $currentPleb->association_status->value > 1)
                                <div class="inline-flex flex-col w-full max-w-lg px-4 py-2 rounded-lg text-sm bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700/60 text-gray-600 dark:text-gray-400">
                                    <div class="flex w-full justify-between items-start">
                                        <div class="flex">
                                            <svg class="shrink-0 fill-current text-yellow-500 mt-[3px] mr-3" width="16" height="16" viewBox="0 0 16 16">
                                                <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm0 12c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1zm1-3H7V4h2v5z"></path>
                                            </svg>
                                            <div>
                                                <div class="font-medium text-gray-800 dark:text-gray-100 mb-1">Dein aktueller Status: {{ $currentPleb->association_status->label() }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </section>

                    </div>

                    <!-- Panel footer -->
                    {{--<footer>
                        <div class="flex flex-col px-6 py-5 border-t border-gray-200 dark:border-gray-700/60">
                            <div class="flex self-end">
                                <button
                                    class="btn dark:bg-[#1B1B1B] border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 text-[#1B1B1B] dark:text-gray-300">
                                    Cancel
                                </button>
                                <button
                                    class="btn bg-gray-900 text-gray-100 hover:bg-[#1B1B1B] dark:bg-gray-100 dark:text-[#1B1B1B] dark:hover:bg-white ml-3">
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </footer>--}}

                </div>

            </div>
        </div>

    </div>
    @endvolt
</x-layouts.app>
