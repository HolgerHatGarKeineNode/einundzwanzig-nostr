<?php

namespace App\Support;

use App\Auth\NostrUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class NostrAuth
{
    /**
     * Login a user by their Nostr pubkey
     */
    public static function login(string $pubkey): void
    {
        Auth::guard('nostr')->loginByPubkey($pubkey);
        Session::regenerate();
    }

    /**
     * Logout the current Nostr user
     */
    public static function logout(): void
    {
        if (Auth::guard('nostr')->check()) {
            Session::flush();
        }
    }

    /**
     * Get the currently authenticated Nostr user
     */
    public static function user(): ?NostrUser
    {
        return Auth::guard('nostr')->user();
    }

    /**
     * Check if a Nostr user is authenticated
     */
    public static function check(): bool
    {
        return Auth::guard('nostr')->check();
    }

    /**
     * Get the current pubkey (convenience method)
     */
    public static function pubkey(): ?string
    {
        return self::user()?->getPubkey();
    }

    /**
     * Get the current pleb (convenience method)
     */
    public static function pleb(): ?object
    {
        return self::user()?->getPleb();
    }
}
