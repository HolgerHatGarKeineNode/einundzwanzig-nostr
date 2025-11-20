<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class NostrUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        return new NostrUser($identifier);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        //
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (isset($credentials['pubkey'])) {
            return new NostrUser($credentials['pubkey']);
        }

        return null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return $user instanceof NostrUser && $user->getPubkey() === ($credentials['pubkey'] ?? null);
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        //
    }
}
