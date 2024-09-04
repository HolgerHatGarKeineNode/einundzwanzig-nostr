<?php

use Livewire\Volt\Component;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Key\Key;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;
use function Laravel\Folio\{middleware};
use function Laravel\Folio\name;
use function Livewire\Volt\{on};

name('welcome');

?>

<x-layouts.app title="Welcome">
    @volt
    <div>
        TEST
    </div>
    @endvolt
</x-layouts.app>
