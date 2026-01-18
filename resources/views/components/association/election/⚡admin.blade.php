<?php

use App\Models\Election;
use Livewire\Component;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

new class extends Component {
    public bool $isAllowed = false;

    public ?string $currentPubkey = null;

    public ?\App\Models\EinundzwanzigPleb $currentPleb = null;

    public ?array $votes = null;

    public ?array $boardVotes = null;

    public ?array $events = null;

    public ?array $boardEvents = null;

    public ?Election $election = null;

    public string $signThisEvent = '';

    public array $plebs = [];

    public array $electionConfig = [];

    protected $listeners = [
        'nostrLoggedOut' => 'handleNostrLoggedOut',
        'nostrLoggedIn' => 'handleNostrLoggedIn',
        'echo:votes,.newVote' => 'handleNewVote',
    ];

    public function mount(Election $election): void
    {
        $this->election = $election;
        $this->loadEvents();
        $this->loadBoardEvents();
        $this->loadVotes();
        $this->loadBoardVotes();
    }

    public function handleNostrLoggedOut(): void
    {
        $this->currentPubkey = null;
        $this->currentPleb = null;
    }

    public function handleNostrLoggedIn($pubkey): void
    {
        $this->currentPubkey = $pubkey;
        $allowedPubkeys = [
            '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033',
            '430169631f2f0682c60cebb4f902d68f0c71c498fd1711fd982f052cf1fd4279',
        ];
        if (in_array($this->currentPubkey, $allowedPubkeys, true)) {
            $this->isAllowed = true;
        }
        dd($this->isAllowed);
    }

    public function handleNewVote(): void
    {
        $this->loadEvents();
        $this->loadBoardEvents();
        $this->loadVotes();
        $this->loadBoardVotes();
    }

    public function loadVotes(): void
    {
        $this->votes = collect($this->events)
            ->map(fn ($event) => [
                'created_at' => $event['created_at'],
                'pubkey' => $event['pubkey'],
                'forpubkey' => $this->fetchProfile($event['content']),
                'type' => str($event['content'])->after(',')->toString(),
            ])
            ->sortByDesc('created_at')
            ->unique(fn ($event) => $event['pubkey'].$event['type'])
            ->values()
            ->groupBy('type')
            ->map(fn ($votes) => [
                'type' => $votes[0]['type'],
                'votes' => $votes->groupBy('forpubkey')->map(fn ($group) => ['count' => $group->count()])->toArray(),
            ])
            ->values()
            ->toArray();
    }

    public function loadBoardVotes(): void
    {
        $this->boardVotes = collect($this->boardEvents)
            ->map(fn ($event) => [
                'created_at' => $event['created_at'],
                'pubkey' => $event['pubkey'],
                'forpubkey' => $this->fetchProfile($event['content']),
                'type' => str($event['content'])->after(',')->toString(),
            ])
            ->sortByDesc('created_at')
            ->values()
            ->groupBy('type')
            ->map(fn ($votes) => [
                'type' => $votes[0]['type'],
                'votes' => $votes->groupBy('forpubkey')->map(fn ($group) => ['count' => $group->count()])->toArray(),
            ])
            ->values()
            ->toArray();
    }

    public function loadEvents(): void
    {
        $this->events = $this->loadNostrEvents([32122]);
    }

    public function loadBoardEvents(): void
    {
        $this->boardEvents = $this->loadNostrEvents([2121]);
    }

    public function fetchProfile($content): string
    {
        $pubkey = str($content)->before(',')->toString();
        $profile = \App\Models\Profile::query()->where('pubkey', $pubkey)->first();
        if (! $profile) {
            \Artisan::call(\App\Console\Commands\Nostr\FetchProfile::class, ['--pubkey' => $pubkey]);
            $profile = \App\Models\Profile::query()->where('pubkey', $pubkey)->first();
        }

        return $profile->pubkey;
    }

    public function loadNostrEvents($kinds): array
    {
        $subscription = new Subscription;
        $subscriptionId = $subscription->setId();
        $filter = new Filter;
        $filter->setKinds($kinds);
        $requestMessage = new RequestMessage($subscriptionId, [$filter]);
        $relaySet = new RelaySet;
        $relaySet->setRelays([new Relay(config('services.relay'))]);
        $request = new Request($relaySet, $requestMessage);
        $response = $request->send();

        return collect($response[config('services.relay')])
            ->map(function ($event) {
                if (! isset($event->event)) {
                    return false;
                }

                return [
                    'id' => $event->event->id,
                    'kind' => $event->event->kind,
                    'content' => $event->event->content,
                    'pubkey' => $event->event->pubkey,
                    'tags' => $event->event->tags,
                    'created_at' => $event->event->created_at,
                ];
            })
            ->filter()
            ->toArray();
    }
};
?>

<div>
    @php
        $positions = [
            'presidency' => ['icon' => 'fa-crown', 'title' => 'Präsidium'],
            'board' => ['icon' => 'fa-users', 'title' => 'Vorstandsmitglieder'],
        ];
    @endphp

    @if($isAllowed)

        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto" x-data="electionAdminCharts()">

        <!-- Dashboard actions -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">
                    Wahl des Vorstands {{ $election->year }}
                </h1>
            </div>

        </div>

        @php
            $president = $positions['presidency'];
            $board = $positions['board'];
        @endphp

            <!-- Cards -->
        <div class="grid gap-y-4">
            <div wire:key="presidency" wire:ignore
                 class="flex flex-col bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100"><i
                            class="fa-sharp-duotone fa-solid {{ $president['icon'] }} w-5 h-5 fill-current text-white mr-4"></i>{{ $president['title'] }}
                    </h2>
                </header>
                <div class="grow">
                    <!-- Change| height attribute to adjust chart height -->
                    <canvas x-ref="chart_presidency" width="724" height="288"
                            style="display: block; box-sizing: border-box; height: 288px; width: 724px;"></canvas>
                </div>
            </div>
            <div wire:key="board" wire:ignore
                 class="flex flex-col bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100"><i
                            class="fa-sharp-duotone fa-solid {{ $board['icon'] }} w-5 h-5 fill-current text-white mr-4"></i>{{ $board['title'] }}
                    </h2>
                </header>
                <div class="grow">
                    <!-- Change| height attribute to adjust chart height -->
                    <canvas x-ref="chart_board" width="724" height="288"
                            style="display: block; box-sizing: border-box; height: 288px; width: 724px;"></canvas>
                </div>
            </div>
        </div>

    </div>

    @else
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <div class="bg-white dark:bg-[#1B1B1B] shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-200">Mitglieder</h3>
                    <p class="mt-1 max-w">
                        Du bist nicht berechtigt, Mitglieder zu bearbeiten.
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
