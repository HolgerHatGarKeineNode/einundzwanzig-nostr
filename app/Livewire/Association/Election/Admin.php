<?php

namespace App\Livewire\Association\Election;

use App\Models\Election;
use Livewire\Component;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

final class Admin extends Component
{
    public bool $isAllowed = false;

    public ?string $currentPubkey = null;

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
}
