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

name('association.elections');

state(['isAllowed' => false]);
state(['currentPubkey' => null]);
state(['elections' => []]);

mount(function () {
    $this->elections = \App\Models\Election::query()
        ->get()
        ->toArray();
});

on([
    'nostrLoggedOut' => function () {
        $this->isAllowed = false;
        $this->currentPubkey = null;
        $this->currentPleb = null;
    },
]);

on([
    'nostrLoggedIn' => function ($pubkey) {
        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()
            ->where('pubkey', $pubkey)->first();
        if ($this->currentPubkey !== '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033') {
            return redirect()->route('association.profile');
        }
        $this->isAllowed = true;
    },
]);

$saveElection = function ($index) {
    $election = $this->elections[$index];
    $electionModel = \App\Models\Election::find($election['id']);
    $electionModel->candidates = $election['candidates'];
    $electionModel->save();
};

?>

<x-layouts.app title="{{ __('Wahlen') }}">
    @volt
    <div x-cloak class="relative flex h-full" x-show="isAllowed" x-data="{isAllowed: $wire.entangle('isAllowed').live}">
        @foreach($elections as $election)
            <div class="w-full sm:w-1/3 p-4">
                <div class="shadow-lg rounded-lg overflow-hidden">
                    {{ $election['year'] }}
                </div>
                <div class="shadow-lg rounded-lg overflow-hidden">
                    <x-textarea wire:model="elections.{{ $loop->index }}.candidates" rows="25" label="candidates" placeholder="" />
                </div>
                <div class="py-2">
                    <x-button label="Speichern" wire:click="saveElection({{ $loop->index }})"/>
                </div>
            </div>
        @endforeach
    </div>
    @endvolt
</x-layouts.app>
