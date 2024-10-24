<?php

use Livewire\Volt\Component;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Key\Key;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

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

name('meetups.table');

?>


<x-layouts.app title="{{ __('Meetups') }}">
    @volt
    <div>
        <livewire:meetup-table />
    </div>
    @endvolt
</x-layouts.app>
