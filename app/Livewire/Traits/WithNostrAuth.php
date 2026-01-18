<?php

namespace App\Livewire\Traits;

use App\Support\NostrAuth;
use Livewire\Attributes\On;

trait WithNostrAuth
{
    public ?string $currentPubkey = null;

    public ?object $currentPleb = null;

    public bool $isAllowed = false;

    public bool $canEdit = false;

    #[On('nostrLoggedIn')]
    public function handleNostrLogin(string $pubkey): void
    {
        NostrAuth::login($pubkey);

        $this->currentPubkey = $pubkey;
        $this->currentPleb = \App\Models\EinundzwanzigPleb::query()
            ->where('pubkey', $pubkey)
            ->first();

        if ($this->currentPleb && in_array($this->currentPleb->npub, config('einundzwanzig.config.current_board'), true)) {
            $this->canEdit = true;
        }

        $this->isAllowed = true;
    }

    #[On('nostrLoggedOut')]
    public function handleNostrLogout(): void
    {
        NostrAuth::logout();

        $this->isAllowed = false;
        $this->currentPubkey = null;
        $this->currentPleb = null;
        $this->canEdit = false;
    }

    public function mountNostrAuth(): void
    {
        if ($user = NostrAuth::user()) {
            $this->currentPubkey = $user->getPubkey();
            $this->currentPleb = $user->getPleb();
            $this->isAllowed = true;

            if ($this->currentPleb && in_array($this->currentPleb->npub, config('einundzwanzig.config.current_board'), true)) {
                $this->canEdit = true;
            }
        }
    }
}
