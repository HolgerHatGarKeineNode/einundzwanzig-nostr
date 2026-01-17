<?php

namespace App\Livewire\Association\Members;

use App\Models\EinundzwanzigPleb;
use App\Support\NostrAuth;
use Livewire\Component;

final class Admin extends Component
{
    public bool $isAllowed = false;

    public ?string $currentPubkey = null;

    public ?EinundzwanzigPleb $currentPleb = null;

    protected $listeners = [
        'nostrLoggedOut' => 'handleNostrLoggedOut',
        'nostrLoggedIn' => 'handleNostrLoggedIn',
    ];

    public function mount(): void
    {
        if (NostrAuth::check()) {
            $this->currentPubkey = NostrAuth::pubkey();
            $this->currentPleb = \App\Models\EinundzwanzigPleb::query()
                ->where('pubkey', $this->currentPubkey)->first();
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
    }

    public function handleNostrLoggedOut(): void
    {
        $this->isAllowed = false;
        $this->currentPubkey = null;
    }

    public function handleNostrLoggedIn($pubkey): void
    {
        NostrAuth::login($pubkey);
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
    }
}
