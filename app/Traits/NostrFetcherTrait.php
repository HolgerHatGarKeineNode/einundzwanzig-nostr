<?php

namespace App\Traits;

use App\Models\Profile;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Key\Key;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

trait NostrFetcherTrait
{

    public function fetchProfile($npubs)
    {
        $hex = collect([]);
        foreach ($npubs as $item) {
            // check if $item is already a hex string
            if (preg_match('/^[0-9a-fA-F]+$/', $item)) {
                $hex->push([
                    'hex' => $item,
                    'npub' => (new Key)->convertPublicKeyToBech32($item),
                ]);
                continue;
            }
            $hex->push([
                'hex' => (new Key)->convertToHex($item),
                'npub' => $item,
            ]);
        }

        $subscription = new Subscription();
        $subscriptionId = $subscription->setId();

        $filter1 = new Filter();
        $filter1->setKinds([0]); // You can add multiple kind numbers
        $filter1->setAuthors($hex->pluck('hex')->toArray()); // You can add multiple author ids
        $filters = [$filter1]; // You can add multiple filters.

        $requestMessage = new RequestMessage($subscriptionId, $filters);

        $relayUrl = 'wss://relay.nostr.band/';
        $relay = new Relay($relayUrl);
        $relay->setMessage($requestMessage);

        $request = new Request($relay, $requestMessage);
        $response = $request->send();

        foreach ($response['wss://relay.nostr.band/'] as $item) {
            try {
                $result = json_decode($item->event->content, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \RuntimeException('Error decoding JSON: ' . $e->getMessage());
            }
            Profile::query()->updateOrCreate(
                ['pubkey' => $item->event->pubkey],
                [
                    'name' => $result['name'] ?? null,
                    'display_name' => $result['display_name'] ?? null,
                    'picture' => $result['picture'] ?? null,
                    'banner' => $result['banner'] ?? null,
                    'website' => $result['website'] ?? null,
                    'about' => $result['about'] ?? null,
                    'nip05' => $result['nip05'] ?? null,
                    'lud16' => $result['lud16'] ?? null,
                    'lud06' => $result['lud06'] ?? null,
                    'deleted' => $result['deleted'] ?? false,
                ]
            );
        }

    }

}
