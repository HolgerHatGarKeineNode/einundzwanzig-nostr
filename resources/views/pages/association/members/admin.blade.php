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

state(['isAllowed' => false]);
state(['currentPubkey' => null]);
state(['members' => []]);

on([
    'nostrLoggedIn' => function ($pubkey) {
        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()
            ->where('pubkey', $pubkey)->first();
        if($this->currentPubkey !== '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033') {
            return redirect()->route('association.profile');
        }
        $this->isAllowed = true;
    },
]);

?>

<x-layouts.app title="{{ __('Mitglieder') }}">
    @volt
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto" x-show="isAllowed" x-data="{isAllowed: $wire.entangle('isAllowed').live}" x-cloak>
        <livewire:einundzwanzig-pleb-table/>
    </div>
    @endvolt
</x-layouts.app>
