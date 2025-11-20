<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;

class NostrSessionGuard extends SessionGuard
{
    public function loginByPubkey(string $pubkey): void
    {
        $user = new NostrUser($pubkey);

        $this->updateSession($user->getAuthIdentifier());

        $this->setUser($user);

        $this->fireLoginEvent($user, false);
    }

    protected function updateSession($id): void
    {
        $this->session->put($this->getName(), $id);
        $this->session->migrate(true);
    }

    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $id = $this->session->get($this->getName());

        if ($id !== null) {
            $this->user = $this->provider->retrieveById($id);
        }

        return $this->user;
    }
}
