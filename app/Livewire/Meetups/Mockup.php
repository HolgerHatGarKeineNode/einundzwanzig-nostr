<?php

namespace App\Livewire\Meetups;

use Livewire\Component;
use swentel\nostr\Event\Event as NostrEvent;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\EventMessage;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

final class Mockup extends Component
{
    public array $events = [];

    public string $title = '';

    public string $description = '';

    public string $signThisEvent = '';

    public function mount(): void
    {
        $this->loadEvents();
    }

    public function loadEvents(): void
    {
        $subscription = new Subscription;
        $subscriptionId = $subscription->setId();

        $filter1 = new Filter;
        $filter1->setKinds([31924]);
        $filter1->setLimit(25);
        $filters = [$filter1];

        $requestMessage = new RequestMessage($subscriptionId, $filters);

        $relays = [
            new Relay('ws://nostream:8008'),
        ];
        $relaySet = new RelaySet;
        $relaySet->setRelays($relays);

        $request = new Request($relaySet, $requestMessage);
        $response = $request->send();

        $this->events = collect($response['ws://nostream:8008'])
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

    public function save(): void
    {
        $note = new NostrEvent;
        $note->setContent($this->description);
        $note->setKind(31924);
        $note->setTags([
            ['d', str()->uuid()->toString()],
            ['title', $this->title],
        ]);
        $this->signThisEvent = $note->toJson();
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
        $relayUrl = 'ws://nostream:8008';
        $relay = new Relay($relayUrl);
        $relay->setMessage($eventMessage);
        $relay->send();

        $this->title = '';
        $this->description = '';
        $this->loadEvents();
    }
}
