<?php

use Livewire\Volt\Component;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Key\Key;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

use function Laravel\Folio\{middleware, name};
use function Livewire\Volt\{state, mount, on, computed};

name('welcome');

?>

<x-layouts.app title="Welcome">
    @volt
    <div>
        TEST
    </div>
    @endvolt
</x-layouts.app>
