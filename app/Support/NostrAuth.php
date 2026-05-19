<?php

namespace App\Support;

use App\Auth\NostrUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use swentel\nostr\Event\Event as NostrEvent;

class NostrAuth
{
    private const CHALLENGE_SESSION_KEY = 'nostr_login_challenge';

    private const CHALLENGE_EXPIRES_SESSION_KEY = 'nostr_login_challenge_expires_at';

    private const CHALLENGE_TTL_SECONDS = 300;

    private const LOGIN_EVENT_KIND = 22242;

    public static function login(string $pubkey): void
    {
        Auth::guard('nostr')->loginByPubkey($pubkey);
        Session::regenerate();
    }

    /**
     * Generate a fresh NIP-42-style login challenge, persist it to the session,
     * and return it. The frontend embeds this challenge into a kind-22242 event
     * that the Nostr signer must sign before we accept the login.
     */
    public static function issueChallenge(): string
    {
        $challenge = bin2hex(random_bytes(32));

        Session::put(self::CHALLENGE_SESSION_KEY, $challenge);
        Session::put(self::CHALLENGE_EXPIRES_SESSION_KEY, now()->addSeconds(self::CHALLENGE_TTL_SECONDS)->timestamp);

        return $challenge;
    }

    /**
     * Verify a signed NIP-42-style login event and log the holder of the pubkey in.
     *
     * Idempotent across concurrent Livewire listeners: once the challenge has been
     * consumed, a second call with the same event still succeeds as long as the
     * caller's session is already authenticated with the matching pubkey.
     *
     * @return string the verified pubkey
     */
    public static function loginWithSignedEvent(mixed $signedEvent): string
    {
        $pubkey = self::verifySignedEvent($signedEvent);

        if (! self::check() || self::pubkey() !== $pubkey) {
            self::login($pubkey);
        }

        return $pubkey;
    }

    /**
     * Verify the cryptographic signature of a kind-22242 event and that its
     * challenge tag matches the value stored on this session. Consumes the
     * stored challenge on success so it cannot be reused.
     *
     * @return string the verified pubkey
     */
    public static function verifySignedEvent(mixed $signedEvent): string
    {
        if (! is_array($signedEvent)) {
            throw ValidationException::withMessages(['nostr' => __('auth.failed')]);
        }

        foreach (['id', 'pubkey', 'created_at', 'kind', 'tags', 'content', 'sig'] as $field) {
            if (! array_key_exists($field, $signedEvent)) {
                throw ValidationException::withMessages(['nostr' => __('auth.failed')]);
            }
        }

        if ((int) $signedEvent['kind'] !== self::LOGIN_EVENT_KIND) {
            throw ValidationException::withMessages(['nostr' => __('auth.failed')]);
        }

        $createdAt = (int) $signedEvent['created_at'];
        if (abs(now()->timestamp - $createdAt) > self::CHALLENGE_TTL_SECONDS) {
            throw ValidationException::withMessages(['nostr' => __('auth.failed')]);
        }

        $challengeFromEvent = null;
        foreach ($signedEvent['tags'] as $tag) {
            if (is_array($tag) && ($tag[0] ?? null) === 'challenge') {
                $challengeFromEvent = (string) ($tag[1] ?? '');
                break;
            }
        }

        if ($challengeFromEvent === null || $challengeFromEvent === '') {
            throw ValidationException::withMessages(['nostr' => __('auth.failed')]);
        }

        $expectedChallenge = Session::get(self::CHALLENGE_SESSION_KEY);
        $expiresAt = (int) Session::get(self::CHALLENGE_EXPIRES_SESSION_KEY, 0);
        $challengeMatchesSession = is_string($expectedChallenge)
            && $expectedChallenge !== ''
            && $expiresAt >= now()->timestamp
            && hash_equals($expectedChallenge, $challengeFromEvent);

        $eventJson = json_encode([
            'id' => (string) $signedEvent['id'],
            'pubkey' => (string) $signedEvent['pubkey'],
            'created_at' => $createdAt,
            'kind' => self::LOGIN_EVENT_KIND,
            'tags' => $signedEvent['tags'],
            'content' => (string) $signedEvent['content'],
            'sig' => (string) $signedEvent['sig'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $sigValid = false;
        try {
            $sigValid = (new NostrEvent)->verify($eventJson);
        } catch (\Throwable) {
            $sigValid = false;
        }

        if (! $sigValid) {
            throw ValidationException::withMessages(['nostr' => __('auth.failed')]);
        }

        $eventPubkey = (string) $signedEvent['pubkey'];

        if ($challengeMatchesSession) {
            Session::forget([self::CHALLENGE_SESSION_KEY, self::CHALLENGE_EXPIRES_SESSION_KEY]);

            return $eventPubkey;
        }

        // Idempotent path: the challenge has already been consumed (e.g. a
        // sibling Livewire listener processed the same event microseconds
        // earlier). Only accept if the current session is already
        // authenticated with this exact pubkey.
        if (self::check() && self::pubkey() === $eventPubkey) {
            return $eventPubkey;
        }

        throw ValidationException::withMessages(['nostr' => __('auth.failed')]);
    }

    public static function logout(): void
    {
        if (Auth::guard('nostr')->check()) {
            Auth::guard('nostr')->logout();
            Session::flush();
        }
    }

    public static function user(): ?NostrUser
    {
        return Auth::guard('nostr')->user();
    }

    public static function check(): bool
    {
        return Auth::guard('nostr')->check();
    }

    public static function pubkey(): ?string
    {
        return self::user()?->getPubkey();
    }
}
