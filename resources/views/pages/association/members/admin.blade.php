<?php

use Livewire\Volt\Component;

use function Livewire\Volt\computed;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;
use function Livewire\Volt\with;
use function Livewire\Volt\updated;
use function Laravel\Folio\{middleware};
use function Laravel\Folio\name;
use function Livewire\Volt\{on};

name('association.members.admin');

state(['currentPubkey' => null]);
state(['members' => []]);

on([
    'nostrLoggedIn' => function ($pubkey) {
        $this->currentPubkey = $pubkey;
    },
]);

?>

<x-layouts.app title="{{ __('Mitglieder') }}">
    @volt
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <livewire:einundzwanzig-pleb-table/>
    </div>
    @endvolt
</x-layouts.app>
