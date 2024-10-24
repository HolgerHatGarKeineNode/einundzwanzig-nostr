<?php

use App\Livewire\Forms\VoteForm;
use App\Models\Vote;
use Livewire\Volt\Component;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Key\Key;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

use function Laravel\Folio\{middleware, name};
use function Livewire\Volt\{state, mount, on, computed, form, with};

name('association.projectSupport.item');

form(VoteForm::class);

state([
    'projectProposal' => fn() => $projectProposal,
    'isAllowed' => false,
    'currentPubkey' => null,
    'currentPleb' => null,
    'reasons' => fn() => $this->getReasons(),
    'ownVoteExists' => false,
    'boardVotes' => fn() => $this->getBoardVotes(),
    'otherVotes' => fn() => $this->getOtherVotes(),
]);

on([
    'nostrLoggedIn' => fn($pubkey) => $this->handleNostrLoggedIn($pubkey),
    'nostrLoggedOut' => fn() => $this->handleNostrLoggedOut(),
]);

$approve = fn() => $this->handleApprove();
$notApprove = fn() => $this->handleNotApprove();

$getReasons = function () {
    return Vote::query()
        ->where('project_proposal_id', $this->projectProposal->id)
        ->where('value', false)
        ->get();
};

$getBoardVotes = function () {
    return Vote::query()
        ->where('project_proposal_id', $this->projectProposal->id)
        ->whereHas('einundzwanzigPleb', fn($q) => $q->whereIn('npub', config('einundzwanzig.config.current_board')))
        ->where('value', true)
        ->get();
};

$getOtherVotes = function () {
    return Vote::query()
        ->where('project_proposal_id', $this->projectProposal->id)
        ->whereDoesntHave(
            'einundzwanzigPleb',
            fn($q) => $q->whereIn('npub', config('einundzwanzig.config.current_board'))
        )
        ->where('value', true)
        ->get();
};

$handleNostrLoggedIn = function ($pubkey) {
    $this->currentPubkey = $pubkey;
    $this->currentPleb = \App\Models\EinundzwanzigPleb::query()->where('pubkey', $pubkey)->first();
    if ($this->currentPleb->association_status->value < 2) {
        return $this->js('alert("Du bist hierzu nicht berechtigt.")');
    }
    $this->isAllowed = true;
    $this->ownVoteExists = Vote::query()
        ->where('project_proposal_id', $this->projectProposal->id)
        ->where('einundzwanzig_pleb_id', $this->currentPleb->id)
        ->exists();
};

$handleNostrLoggedOut = function () {
    $this->isAllowed = false;
    $this->currentPubkey = null;
    $this->currentPleb = null;
};

$handleApprove = function () {
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
};

$handleNotApprove = function () {
    $this->form->validate();

    Vote::query()->updateOrCreate([
        'project_proposal_id' => $this->projectProposal->id,
        'einundzwanzig_pleb_id' => $this->currentPleb->id,
    ], [
        'value' => false,
        'reason' => $this->form->reason,
    ]);
    $this->form->reset();
    $this->ownVoteExists = true;
    $this->reasons = $this->getReasons();
};

?>

<x-layouts.app title="{{ $projectProposal->name }}"
               :seo="new SEOData(image: $projectProposal->getFirstMediaUrl('main'), description: str($projectProposal->description)->limit(100, '...', true))">
    @volt
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full" x-data="nostrDefault(@this)" x-cloak
         x-show="isAllowed">

        <!-- Page content -->
        <div class="max-w-5xl mx-auto flex flex-col lg:flex-row lg:space-x-8 xl:space-x-16">

            <!-- Content -->
            <div>
                <div class="mb-6">
                    <a class="btn-sm px-3 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 text-gray-800 dark:text-gray-300"
                       href="{{ route('association.projectSupport') }}"
                    >
                        <svg class="fill-current text-gray-400 dark:text-gray-500 mr-2" width="7" height="12"
                             viewBox="0 0 7 12">
                            <path d="M5.4.6 6.8 2l-4 4 4 4-1.4 1.4L0 6z"></path>
                        </svg>
                        <span>Zurück zur Übersicht</span>
                    </a>
                </div>
                <div class="text-sm font-semibold text-violet-500 uppercase mb-2">
                    {{ $projectProposal->created_at->format('D d M, Y') }}
                </div>
                <header class="mb-4">
                    <!-- Title -->
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold mb-2">
                        {{ $projectProposal->name }}
                    </h1>
                    <x-markdown>
                        {!! $projectProposal->description !!}
                    </x-markdown>
                </header>

                <div class="space-y-3 sm:flex sm:items-center sm:justify-between sm:space-y-0 mb-6">
                    <!-- Author -->
                    <div class="flex items-center sm:mr-4">
                        <a class="block mr-2 shrink-0" href="#0">
                            <img class="rounded-full" src="{{ $projectProposal->einundzwanzigPleb->profile->picture }}"
                                 width="32" height="32" alt="User 04">
                        </a>
                        <div class="text-sm whitespace-nowrap">Eingereicht von
                            <div
                                class="font-semibold text-gray-800 dark:text-gray-100">{{ $projectProposal->einundzwanzigPleb->profile->name }}</div>
                        </div>
                    </div>
                    <!-- Right side -->
                    <div class="flex flex-wrap items-center sm:justify-end space-x-2">
                        <!-- Tags -->
                        <div
                            class="text-xs inline-flex items-center font-medium border border-gray-200 dark:border-gray-700/60 text-gray-600 dark:text-gray-400 rounded-full text-center px-2.5 py-1">
                            <a target="_blank" href="{{ $projectProposal->website }}"><span>Webseite</span></a>
                        </div>
                        <div
                            class="text-xs inline-flex font-medium uppercase bg-green-500/20 text-green-700 rounded-full text-center px-2.5 py-1">
                            {{ number_format($projectProposal->support_in_sats, 0, ',', '.') }} Sats
                        </div>
                    </div>
                </div>

                <figure class="mb-6">
                    <img class="rounded-sm h-48" src="{{ $projectProposal->getFirstMediaUrl('main') }}" alt="Meetup">
                </figure>

                <hr class="my-6 border-t border-gray-100 dark:border-gray-700/60">

                <!-- Reasons -->
                <div>
                    <h2 class="text-xl leading-snug text-gray-800 dark:text-gray-100 font-bold mb-2">
                        Ablehnungen ({{ count($reasons) }})
                    </h2>
                    <ul class="space-y-5 my-6">
                        @foreach($reasons as $reason)
                            <li class="flex items-start">
                                <a class="block mr-3 shrink-0" href="#0">
                                    <img class="rounded-full" src="{{ $reason->einundzwanzigPleb->profile->picture }}"
                                         width="32" height="32"
                                         alt="{{ $reason->einundzwanzigPleb->profile->name }}">
                                </a>
                                <div class="grow">
                                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2">
                                        {{ $reason->einundzwanzigPleb->profile->name }}
                                    </div>
                                    <div class="italic">{{ $reason->reason }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="space-y-4">

                <!-- 1st block -->
                <div class="bg-white dark:bg-gray-800 p-5 shadow-sm rounded-xl lg:w-72 xl:w-80">
                    @if(!$ownVoteExists)
                        <div class="space-y-2">
                            <button
                                wire:click="approve"
                                class="btn w-full bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                                <i class="fill-current shrink-0 fa-sharp-duotone fa-solid fa-thumbs-up"></i>
                                <span class="ml-1">Zustimmen</span>
                            </button>
                            <button
                                wire:click="notApprove"
                                class="btn w-full bg-red-900 text-red-100 hover:bg-red-800 dark:bg-red-100 dark:text-red-800 dark:hover:bg-red-400">
                                <i class="fill-current shrink-0 fa-sharp-duotone fa-solid fa-thumbs-down"></i>
                                <span class="ml-1">Ablehnen</span>
                            </button>
                            <x-textarea wire:model="form.reason" label="Grund für deine Ablehnung"/>
                        </div>
                    @else
                        <div class="space-y-2">
                            <p>Du hast bereits abgestimmt.</p>
                        </div>
                    @endif
                </div>

                <!-- 2nd block -->
                <div class="bg-white dark:bg-gray-800 p-5 shadow-sm rounded-xl lg:w-72 xl:w-80">
                    <div class="flex justify-between space-x-1 mb-5">
                        <div class="text-sm text-gray-800 dark:text-gray-100 font-semibold">
                            Zustimmungen des Vorstands ({{ count($boardVotes) }})
                        </div>
                    </div>
                    <ul class="space-y-3">
                        @foreach($boardVotes as $vote)
                            <li>
                                <div class="flex justify-between">
                                    <div class="grow flex items-center">
                                        <div class="relative mr-3">
                                            <img class="w-8 h-8 rounded-full"
                                                 src="{{ $vote->einundzwanzigPleb->profile->picture }}" width="32"
                                                 height="32" alt="{{ $vote->einundzwanzigPleb->profile->name }}">
                                        </div>
                                        <div class="truncate">
                                            <span class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                                {{ $vote->einundzwanzigPleb->profile->name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- 3rd block -->
                <div class="bg-white dark:bg-gray-800 p-5 shadow-sm rounded-xl lg:w-72 xl:w-80">
                    <div class="flex justify-between space-x-1 mb-5">
                        <div class="text-sm text-gray-800 dark:text-gray-100 font-semibold">
                            Zustimmungen der übrigen Mitglieder ({{ count($otherVotes) }})
                        </div>
                    </div>
                    <ul class="space-y-3">
                        @foreach($otherVotes as $vote)
                            <li>
                                <div class="flex items-center justify-between">
                                    <div class="grow flex items-center">
                                        <div class="relative mr-3">
                                            <img class="w-8 h-8 rounded-full"
                                                 src="{{ $vote->einundzwanzigPleb->profile->picture }}" width="32"
                                                 height="32" alt="{{ $vote->einundzwanzigPleb->profile->name }}">
                                        </div>
                                        <div class="truncate">
                                        <span
                                            class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                            {{ $vote->einundzwanzigPleb->profile->name }}
                                        </span>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

            </div>

        </div>

    </div>
    @endvolt
</x-layouts.app>
