<?php

namespace App\Http\Controllers\Api\Nostr;

use App\Http\Controllers\Controller;
use App\Models\EinundzwanzigPleb;
use App\Models\Profile;
use App\Traits\NostrFetcherTrait;
use Illuminate\Http\Request;
use swentel\nostr\Key\Key;

class GetProfile extends Controller
{
    use NostrFetcherTrait;

    public function __invoke($key, Request $request)
    {
        if (!Profile::query()->where('pubkey', $key)->exists()) {
            $this->fetchProfile([$key]);
        }

        // create EinundzwanzigPleb if not exists
        EinundzwanzigPleb::query()->firstOrCreate(['pubkey' => $key], [
            'npub' => (new Key)->convertPublicKeyToBech32($key),
        ]);

        return Profile::query()
            ->where('pubkey', $key)
            ->first();
    }
}
