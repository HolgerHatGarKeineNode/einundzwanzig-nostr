<?php

namespace App\Livewire\Association\Election;

use App\Models\EinundzwanzigPleb;
use App\Models\Election;
use App\Models\Profile;
use App\Support\NostrAuth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use swentel\nostr\Event\Event as NostrEvent;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\EventMessage;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

final class Show extends Component
{
    public bool $isAllowed = false;

    public bool $showLog = false;

    public ?string $currentPubkey = null;

    public ?EinundzwanzigPleb $currentPleb = null;

    public array $events = [];

    public array $boardEvents = [];

    public Election $election;

    public array $plebs = [];

    public string $search = '';

    public string $signThisEvent = '';

    public bool $isNotClosed = true;

    public array $positions = [
        'presidency' => ['icon' => 'fa-crown', 'title' => 'Präsidium'],
        'board' => ['icon' => 'fa-users', 'title' => 'Vizepräsidium'],
    ];

    protected $listeners = [
        'nostrLoggedIn' => 'handleNostrLoggedIn',
        'nostrLoggedOut' => 'handleNostrLoggedOut',
        'echo:votes,.newVote' => 'handleNewVote',
    ];

    #[Computed]
    public function loadedEvents(): array
    {
        return collect($this->events)
            ->map(function ($event) {
                $profile = Profile::query()
                    ->where('pubkey', $event['pubkey'])
                    ->first()
                    ?->toArray();
                $votedFor = Profile::query()
                    ->where('pubkey', str($event['content'])->before(',')->toString())
                    ->first()
                    ?->toArray();

                return [
                    'id' => $event['id'],
                    'kind' => $event['kind'],
                    'content' => $event['content'],
                    'pubkey' => $event['pubkey'],
                    'tags' => $event['tags'],
                    'created_at' => $event['created_at'],
                    'profile' => $profile,
                    'votedFor' => $votedFor,
                    'type' => str($event['content'])->after(',')->toString(),
                ];
            })
            ->sortByDesc('created_at')
            ->unique(fn ($event) => $event['pubkey'].$event['type'])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function loadedBoardEvents(): array
    {
        return collect($this->boardEvents)
            ->map(function ($event) {
                $profile = Profile::query()
                    ->where('pubkey', $event['pubkey'])
                    ->first()
                    ?->toArray();
                $votedFor = Profile::query()
                    ->where('pubkey', str($event['content'])->before(',')->toString())
                    ->first()
                    ?->toArray();

                return [
                    'id' => $event['id'],
                    'kind' => $event['kind'],
                    'content' => $event['content'],
                    'pubkey' => $event['pubkey'],
                    'tags' => $event['tags'],
                    'created_at' => $event['created_at'],
                    'profile' => $profile,
                    'votedFor' => $votedFor,
                    'type' => str($event['content'])->after(',')->toString(),
                ];
            })
            ->sortByDesc('created_at')
            ->values()
            ->toArray();
    }

    #[Computed]
    public function electionConfig(): array
    {
        $loadedEvents = $this->loadedEvents();

        return collect(json_decode($this->election->candidates, true, 512, JSON_THROW_ON_ERROR))
            ->map(function ($c) use ($loadedEvents) {
                $candidates = Profile::query()
                    ->whereIn('pubkey', $c['c'])
                    ->get()
                    ->map(function ($p) use ($loadedEvents, $c) {
                        $votedClass = ' bg-green-500/20 text-green-700';
                        $notVotedClass = ' bg-gray-500/20 text-gray-100';
                        $hasVoted = $loadedEvents
                            ->filter(fn ($e) => $e['type'] === $c['type'] && $e['pubkey'] === $this->currentPubkey)
                            ->firstWhere('votedFor.pubkey', $p->pubkey);

                        return [
                            'pubkey' => $p->pubkey,
                            'name' => $p->name,
                            'picture' => $p->picture,
                            'votedClass' => $hasVoted ? $votedClass : $notVotedClass,
                        ];
                    });

                return [
                    'type' => $c['type'],
                    'c' => $c['c'],
                    'candidates' => $candidates,
                ];
            })
            ->toArray();
    }

    #[Computed]
    public function electionConfigBoard(): array
    {
        $loadedBoardEvents = $this->loadedBoardEvents();

        return collect(json_decode($this->election->candidates, true, 512, JSON_THROW_ON_ERROR))
            ->map(function ($c) use ($loadedBoardEvents) {
                $candidates = Profile::query()
                    ->whereIn('pubkey', $c['c'])
                    ->get()
                    ->map(function ($p) use ($loadedBoardEvents, $c) {
                        $votedClass = ' bg-green-500/20 text-green-700';
                        $notVotedClass = ' bg-gray-500/20 text-gray-100';
                        $hasVoted = $loadedBoardEvents
                            ->filter(fn ($e) => $e['type'] === $c['type'] && $e['pubkey'] === $this->currentPubkey)
                            ->firstWhere('votedFor.pubkey', $p->pubkey);

                        return [
                            'pubkey' => $p->pubkey,
                            'name' => $p->name,
                            'picture' => $p->picture,
                            'votedClass' => $hasVoted ? $votedClass : $notVotedClass,
                            'hasVoted' => $hasVoted,
                        ];
                    });

                return [
                    'type' => $c['type'],
                    'c' => $c['c'],
                    'candidates' => $candidates,
                ];
            })
            ->toArray();
    }

    public function mount(Election $election): void
    {
        $this->election = $election;
        $this->plebs = EinundzwanzigPleb::query()
            ->with(['profile'])
            ->whereIn('association_status', [3, 4])
            ->orderBy('association_status', 'desc')
            ->get()
            ->toArray();
        $this->loadEvents();
        $this->loadBoardEvents();
        if ($this->election->end_time?->isPast() || ! config('services.voting')) {
            $this->isNotClosed = false;
        }
    }

    public function updatedSearch($value): void
    {
        $this->plebs = EinundzwanzigPleb::query()
            ->with(['profile'])
            ->whereIn('association_status', [3, 4])
            ->where(fn ($query) => $query
                ->where('pubkey', 'like', "%$value%")
                ->orWhereHas('profile', fn ($query) => $query->where('name', 'ilike', "%$value%")))
            ->orderBy('association_status', 'desc')
            ->get()
            ->toArray();
    }

    public function handleNostrLoggedIn($pubkey): void
    {
        NostrAuth::login($pubkey);
        $this->currentPubkey = $pubkey;
        $this->currentPleb = EinundzwanzigPleb::query()->where('pubkey', $pubkey)->first();
        $logPubkeys = [
            '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033',
            '430169631f2f0682c60cebb4f902d68f0c71c498fd1711fd982f052cf1fd4279',
        ];
        if (in_array($this->currentPubkey, $logPubkeys, true)) {
            $this->showLog = true;
            $this->isAllowed = true;
        }
    }

    public function handleNostrLoggedOut(): void
    {
        $this->isAllowed = false;
        $this->currentPubkey = null;
        $this->currentPleb = null;
    }

    public function handleNewVote(): void
    {
        $this->loadEvents();
        $this->loadBoardEvents();
    }

    public function loadEvents(): void
    {
        $this->events = $this->loadNostrEvents([32122]);
    }

    public function loadBoardEvents(): void
    {
        $this->boardEvents = $this->loadNostrEvents([2121]);
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

    public function vote($pubkey, $type, $board = false): void
    {
        if ($this->election->end_time?->isPast()) {
            $this->isNotClosed = false;

            return;
        }
        $note = new NostrEvent;
        $note->setKind($board ? 2121 : 32122);
        if (! $board) {
            $dTag = sprintf('%s,%s,%s', $this->currentPleb->pubkey, date('Y'), $type);
            $note->setTags([['d', $dTag]]);
        }
        $note->setContent("$pubkey,$type");
        $this->signThisEvent = $note->toJson();
    }

    public function checkElection(): void
    {
        if ($this->election->end_time?->isPast()) {
            $this->isNotClosed = false;
        }
    }

    public function signEvent($event): void
    {
        $note = new NostrEvent;
        $note->setId($event['id']);
        $note->setSignature($event['sig']);
        $note->setKind($event['kind']);
        $note->setContent($event['content']);
        $note->setPublicKey($event['pubkey']);
        $note->setTags($event['tags']);
        $note->setCreatedAt($event['created_at']);
        $eventMessage = new EventMessage($note);
        $relay = new Relay(config('services.relay'));
        $relay->setMessage($eventMessage);
        $relay->send();
        \App\Support\Broadcast::on('votes')->as('newVote')->sendNow();
    }
}
