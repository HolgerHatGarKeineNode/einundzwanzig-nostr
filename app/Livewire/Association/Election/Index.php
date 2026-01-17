<?php

namespace App\Livewire\Association\Election;

use App\Models\EinundzwanzigPleb;
use App\Models\Election;
use App\Support\NostrAuth;
use Livewire\Component;

final class Index extends Component
{
    public bool $isAllowed = false;

    public ?string $currentPubkey = null;

    public ?EinundzwanzigPleb $currentPleb = null;

    public array $elections = [];

    protected $listeners = [
        'nostrLoggedOut' => 'handleNostrLoggedOut',
        'nostrLoggedIn' => 'handleNostrLoggedIn',
    ];

    public function mount(): void
    {
        $this->elections = Election::query()
            ->get()
            ->toArray();
        if (NostrAuth::check()) {
            $this->currentPubkey = NostrAuth::pubkey();
            $logPubkeys = [
                '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033',
                '430169631f2f0682c60cebb4f902d68f0c71c498fd1711fd982f052cf1fd4279',
            ];
            if (in_array($this->currentPubkey, $logPubkeys, true)) {
                $this->isAllowed = true;
            }
        }
    }

    public function handleNostrLoggedOut(): void
    {
        $this->isAllowed = false;
        $this->currentPubkey = null;
        $this->currentPleb = null;
    }

    public function handleNostrLoggedIn($pubkey): void
    {
        NostrAuth::login($pubkey);
        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()
            ->where('pubkey', $pubkey)->first();
        $logPubkeys = [
            '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033',
            '430169631f2f0682c60cebb4f902d68f0c71c498fd1711fd982f052cf1fd4279',
        ];
        if (in_array($this->currentPubkey, $logPubkeys, true)) {
            $this->isAllowed = true;
        }
    }

    public function saveElection($index): void
    {
        $election = $this->elections[$index];
        $electionModel = Election::find($election['id']);
        $electionModel->candidates = $election['candidates'];
        $electionModel->save();
    }
}
