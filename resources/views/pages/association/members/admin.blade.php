<?php

use Livewire\Volt\Component;

use function Livewire\Volt\{
    computed,
    mount,
    state,
    with,
    updated,
    on
};
use function Laravel\Folio\{
    middleware,
    name
};

name('association.members.admin');

state(['isAllowed' => false]);
state(['currentPubkey' => null]);
state(['members' => []]);

on([
    'nostrLoggedOut' => function () {
        $this->isAllowed = false;
        $this->currentPubkey = null;
    },
]);

on([
    'nostrLoggedIn' => function ($pubkey) {
        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()
            ->where('pubkey', $pubkey)->first();
        $allowedPubkeys = [
            '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033',
            '430169631f2f0682c60cebb4f902d68f0c71c498fd1711fd982f052cf1fd4279',
        ];
        if (!in_array($this->currentPubkey, $allowedPubkeys, true)) {
            return $this->js('alert("Du bist nicht berechtigt, Mitglieder zu bearbeiten.")');
        }
        $this->isAllowed = true;
    },
]);

?>

<x-layouts.app title="{{ __('Mitglieder') }}">
    @volt
    <div>
        @if($isAllowed)
            <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
                <livewire:einundzwanzig-pleb-table/>
            </div>
        @else
            <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
                <div class="bg-white dark:bg-[#1B1B1B] shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-200">Mitglieder</h3>
                        <p class="mt-1 max-w">
                            Du bist nicht berechtigt, Mitglieder zu bearbeiten.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
    @endvolt
</x-layouts.app>
