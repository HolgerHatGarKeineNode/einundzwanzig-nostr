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

mount(function () {
    if (\App\Support\NostrAuth::check()) {
        $this->currentPubkey = \App\Support\NostrAuth::pubkey();
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()
            ->where('pubkey', $this->currentPubkey )->first();
        $allowedPubkeys = [
            '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033',
            '430169631f2f0682c60cebb4f902d68f0c71c498fd1711fd982f052cf1fd4279',
            '7acf30cf60b85c62b8f654556cc21e4016df8f5604b3b6892794f88bb80d7a1d',
            'f240be2b684f85cc81566f2081386af81d7427ea86250c8bde6b7a8500c761ba',
            '19e358b8011f5f4fc653c565c6d4c2f33f32661f4f90982c9eedc292a8774ec3',
            'acbcec475a1a4f9481939ecfbd1c3d111f5b5a474a39ae039bbc720fdd305bec',
        ];
        if (in_array($this->currentPubkey, $allowedPubkeys, true)) {
            $this->isAllowed = true;
        }
    }
});

on([
    'nostrLoggedOut' => function () {
        $this->isAllowed = false;
        $this->currentPubkey = null;
    },
]);

on([
    'nostrLoggedIn' => function ($pubkey) {
        \App\Support\NostrAuth::login($pubkey);
        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()
            ->where('pubkey', $pubkey)->first();
        $allowedPubkeys = [
            '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033',
            '430169631f2f0682c60cebb4f902d68f0c71c498fd1711fd982f052cf1fd4279',
            '7acf30cf60b85c62b8f654556cc21e4016df8f5604b3b6892794f88bb80d7a1d',
            'f240be2b684f85cc81566f2081386af81d7427ea86250c8bde6b7a8500c761ba',
            '19e358b8011f5f4fc653c565c6d4c2f33f32661f4f90982c9eedc292a8774ec3',
            'acbcec475a1a4f9481939ecfbd1c3d111f5b5a474a39ae039bbc720fdd305bec',
        ];
        if (in_array($this->currentPubkey, $allowedPubkeys, true)) {
            $this->isAllowed = true;
        }
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
