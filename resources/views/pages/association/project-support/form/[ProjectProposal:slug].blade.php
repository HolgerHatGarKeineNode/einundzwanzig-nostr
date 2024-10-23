<?php

use Livewire\Volt\Component;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Key\Key;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

use function Laravel\Folio\{middleware};
use function Laravel\Folio\name;
use function Livewire\Volt\{state, mount, on, computed};

name('association.projectSupport.form');

state([
    'projectProposal' => fn() => $projectProposal,
]);

?>

<x-layouts.app title="Welcome">
    @volt
    <div>
        @dd($projectProposal)
    </div>
    @endvolt
</x-layouts.app>
