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

use function Livewire\Volt\{
    computed,
    mount,
    state,
    with,
    on
};
use function Laravel\Folio\{
    middleware,
    name
};

name('meetups.mockup');

state(['events' => []]);
state(['title' => '']);
state(['description' => '']);
state(['signThisEvent' => '']);

mount(function () {
    $this->loadEvents();
});

$loadEvents = function() {
    $subscription = new Subscription();
    $subscriptionId = $subscription->setId();

    $filter1 = new Filter();
    $filter1->setKinds([31924]); // You can add multiple kind numbers
    $filter1->setLimit(25); // Limit to fetch only a maximum of 25 events
    $filters = [$filter1]; // You can add multiple filters.

    $requestMessage = new RequestMessage($subscriptionId, $filters);

    $relays = [
        new Relay('ws://nostream:8008'),
    ];
    $relaySet = new RelaySet();
    $relaySet->setRelays($relays);

    $request = new Request($relaySet, $requestMessage);
    $response = $request->send();

    $this->events = collect($response['ws://nostream:8008'])
        ->map(fn($event)
            => [
            'id' => $event->event->id,
            'kind' => $event->event->kind,
            'content' => $event->event->content,
            'pubkey' => $event->event->pubkey,
            'tags' => $event->event->tags,
            'created_at' => $event->event->created_at,
        ])->toArray();
};

$save = function () {
    $note = new NostrEvent();
    $note->setContent($this->description);
    $note->setKind(31924);
    $note->setTags([
        ['d', str()->uuid()->toString()],
        ['title', $this->title],
    ]);
    $this->signThisEvent = $note->toJson();
};

$signEvent = function ($event) {
    $note = new NostrEvent();
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
    $result = $relay->send();

    $this->title = '';
    $this->description = '';
    $this->loadEvents();
};

?>

<x-layouts.app title="{{ __('Mockup') }}">
    @volt
    <div class="relative" x-data="nostrApp(@this)">
        <div class="flex items-center space-x-2 mt-12">
            <div>
                <x-input wire:model.live.debounce="title" label="Title"/>
            </div>
            <div>
                <x-textarea wire:model.live.debounce="description" label="Description"/>
            </div>
            <div>
                <x-button wire:click="save" label="Save"/>
            </div>
        </div>
        <h1 class="text-2x font-bold py-6">Meetups</h1>
        <ul class="border-t border-white space-y-4 divide-y divide-white">
            @foreach($events as $event)
                <li>
                    <div class="flex items">
                        <div class="flex items-center space-x-2">
                            <div>
                                Name: {{ collect($event['tags'])->firstWhere(0, 'title')[1] }}
                            </div>
                            <div>
                                Beschreibung: {{ $event['content'] }}
                            </div>
                            <div>
                                @dump($event)
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
    @endvolt
</x-layouts.app>
