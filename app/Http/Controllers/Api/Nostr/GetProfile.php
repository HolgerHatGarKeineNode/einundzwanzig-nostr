<?php

namespace App\Http\Controllers\Api\Nostr;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Traits\NostrFetcherTrait;
use Illuminate\Http\Request;

class GetProfile extends Controller
{
    use NostrFetcherTrait;

    public function __invoke($key, Request $request)
    {
        if (!Profile::query()->where('pubkey', $key)->exists()) {
            $this->fetchProfile([$key]);
        }

        return Profile::query()
            ->where('pubkey', $key)
            ->first();
    }
}
