<?php

use Livewire\Volt\Component;

use swentel\nostr\Filter\Filter;
use swentel\nostr\Key\Key;
use swentel\nostr\Message\EventMessage;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;
use swentel\nostr\Event\Event as NostrEvent;
use swentel\nostr\Sign\Sign;

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;
use function Livewire\Volt\with;
use function Livewire\Volt\updated;
use function Laravel\Folio\{middleware};
use function Laravel\Folio\name;
use function Livewire\Volt\{on};

name('association.election.admin');

state(['currentPubkey' => null]);
state(['votes' => null]);
state(['events' => null]);
state(['election' => fn() => $election]);
state(['signThisEvent' => '']);
state([
    'plebs' => fn()
        => \App\Models\EinundzwanzigPleb::query()
        ->with([
            'profile',
        ])
        ->whereIn('association_status', [3, 4])
        ->orderBy('association_status', 'desc')
        ->get()
        ->toArray(),
]);
state([
    'electionConfig' => function () {
        return collect(json_decode($this->election->candidates, true, 512, JSON_THROW_ON_ERROR))
            ->map(function ($c) {
                $candidates = \App\Models\Profile::query()
                    ->whereIn('pubkey', $c['c'])
                    ->get()
                    ->map(fn($p)
                        => [
                        'pubkey' => $p->pubkey,
                        'name' => $p->name,
                        'picture' => $p->picture,
                    ]);

                return [
                    'type' => $c['type'],
                    'c' => $c['c'],
                    'candidates' => $candidates,
                ];
            });
    },
]);

mount(function () {
    $this->loadEvents();
    $this->loadVotes();
});

on([
    'nostrLoggedIn' => function ($pubkey) {
        $this->currentPubkey = $pubkey;
    },
]);

on([
    'echo:votes,.newVote' => function () {
        $this->loadEvents();
        $this->loadVotes();
    },
]);

$loadVotes = function () {
    $votes = collect($this->events)
        ->map(function ($event) {
            $votedFor = \App\Models\Profile::query()
                ->where('pubkey', str($event['content'])->before(',')->toString())
                ->first()
                ->toArray();

            return [
                'created_at' => $event['created_at'],
                'pubkey' => $event['pubkey'],
                'forpubkey' => $votedFor['pubkey'],
                'type' => str($event['content'])->after(',')->toString(),
            ];
        })
        ->sortByDesc('created_at')
        ->unique(fn($event) => $event['pubkey'] . $event['type'])
        ->values()
        ->toArray();

    $this->votes = collect($votes)
        ->groupBy('type')
        ->map(fn($votes)
            => [
            'type' => $votes[0]['type'],
            'votes' => collect($votes)
                ->groupBy('forpubkey')
                ->map(fn($group) => ['count' => $group->count()])
                ->toArray(),
        ])
        ->values()
        ->toArray();
};

$loadEvents = function () {
    $subscription = new Subscription();
    $subscriptionId = $subscription->setId();

    $filter1 = new Filter();
    $filter1->setKinds([2121]); // You can add multiple kind numbers
    $filters = [$filter1]; // You can add multiple filters.

    $requestMessage = new RequestMessage($subscriptionId, $filters);

    $relays = [
        new Relay(config('services.relay')),
    ];
    $relaySet = new RelaySet();
    $relaySet->setRelays($relays);

    $request = new Request($relaySet, $requestMessage);
    $response = $request->send();

    // Check for errors in the response
    if (isset($response[config('services.relay')][0]) && isset($response[config('services.relay')][0][0]) && $response[config('services.relay')][0][0] === 'ERROR') {
        abort(500, 'Kann keine Events laden. Nostr Relay antwortet nicht.');
    }

    $this->events = collect($response[config('services.relay')])
        ->map(fn($event)
            => [
            'id' => $event->event->id,
            'kind' => $event->event->kind,
            'content' => $event->event->content,
            'pubkey' => $event->event->pubkey,
            'tags' => $event->event->tags,
            'created_at' => $event->event->created_at,
        ])
        ->toArray();
};

?>

<x-layouts.app title="{{ __('Wahl Manager') }}">
    @volt
    @php
        $positions = [
            'presidency' => ['icon' => 'fa-crown', 'title' => 'Präsidium'],
            'vice_president' => ['icon' => 'fa-user-group-crown', 'title' => 'Vizepräsidium'],
            'finances' => ['icon' => 'fa-bitcoin-sign', 'title' => 'Finanzen'],
            'secretary' => ['icon' => 'fa-stapler', 'title' => 'Revisionsstelle'],
            'press_officer' => ['icon' => 'fa-newspaper', 'title' => 'Pressewart'],
            'it_manager' => ['icon' => 'fa-server', 'title' => 'Technikwart'],
        ];
    @endphp

    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto" x-data="electionAdminCharts(@this)">

        <!-- Dashboard actions -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">
                    Wahl des Vorstands {{ $election->year }}
                </h1>
            </div>

        </div>

        <!-- Cards -->
        <div class="grid grid-cols-12 gap-6">

            @foreach($positions as $key => $position)
                <div wire:key="pos_{{ $key }}" wire:ignore
                     class="flex flex-col col-span-full sm:col-span-6 bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                    <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                        <h2 class="font-semibold text-gray-800 dark:text-gray-100"><i
                                class="fa-sharp-duotone fa-solid {{ $position['icon'] }} w-5 h-5 fill-current text-white mr-4"></i>{{ $position['title'] }}
                        </h2>
                    </header>
                    <div class="grow">
                        <!-- Change the height attribute to adjust the chart height -->
                        <canvas x-ref="chart_{{ $key }}" width="724" height="288"
                                style="display: block; box-sizing: border-box; height: 288px; width: 724px;"></canvas>
                    </div>
                </div>
            @endforeach

        </div>

    </div>

    @endvolt
</x-layouts.app>
