<?php

namespace App\Traits;

use App\Models\Event;
use App\Models\Profile;
use App\Models\RenderedEvent;
use swentel\nostr\Key\Key;

trait NostrEventRendererTrait
{
    public function renderContentToHtml(Event $event): void
    {
        $content = json_decode($event->json, true, 512, JSON_THROW_ON_ERROR)['content'];
        $profile = Profile::query()->where('pubkey', $event->pubkey)->first();
        if ($profile && $profile->name) {
            $name = $profile->name;
        } elseif ($profile && ! empty($profile->display_name)) {
            $name = $profile->display_name;
        } else {
            $name = 'Anonymous';
        }

        $content = $this->nprofile1($content);
        $content = $this->images($content);
        $content = $this->youtube($content);
        $content = $this->npub1($content);

        RenderedEvent::query()->updateOrCreate([
            'event_id' => $event->event_id,
        ], [
            'html' => $content,
            'profile_image' => $profile && $profile->picture !== '' ? $profile->picture : 'https://robohash.org/'.$profile->pubkey,
            'profile_name' => $name,
        ]);
    }

    protected function images($content): string
    {
        // we need to find all image urls by looking for the extension
        // and replace them with the img tag
        $pattern = '/(https?:\/\/.*\.(?:png|jpg|jpeg|gif|webp))/';
        $replacement = '<div class="w-96 group aspect-h-7 aspect-w-10"><img class="pointer-events-none object-cover" src="$1" alt="image" /></div>';

        return preg_replace($pattern, $replacement, $content);
    }

    protected function npub1($content): string
    {
        // Pattern to match nostr:npub1 elements, optionally followed by a non-alphanumeric character
        $pattern = '/(nostr:npub1[a-zA-Z0-9]+)(\W?)/';
        // find all matches of the pattern
        preg_match_all($pattern, $content, $matches);
        // loop through all matches
        foreach ($matches[1] as $match) {
            $pubkey = (new Key)->convertToHex(str($match)->after('nostr:'));
            $profile = Profile::query()->where('pubkey', $pubkey)->first();
            if ($profile && $profile->name) {
                $name = $profile->name;
            } elseif ($profile && ! empty($profile->display_name)) {
                $name = $profile->display_name;
            } else {
                $name = 'Anonymous';
            }
            // replace the match with the profile name
            $content = str_replace($match, $name, $content);
        }

        return $content;
    }

    protected function nprofile1($content): string
    {
        // todo: implement this

        return $content;
    }

    protected function youtube($content): string
    {
        // Pattern to match YouTube short URLs like https://youtu.be/ddvHagjmRJY?feature=shared
        $pattern1 = '/https:\/\/youtu.be\/([a-zA-Z0-9-_]+)\??.*/';
        $replacement1 = '<iframe width="560" height="315" src="https://www.youtube.com/embed/$1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';

        // Pattern to match YouTube long URLs like https://www.youtube.com/watch?v=tiNZoDBGhdo
        $pattern2 = '/https:\/\/www.youtube.com\/watch\?v=([a-zA-Z0-9-_]+)\??.*/';
        $replacement2 = '<iframe width="560" height="315" src="https://www.youtube.com/embed/$1" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';

        // Replace both patterns in the content
        $content = preg_replace($pattern1, $replacement1, $content);
        $content = preg_replace($pattern2, $replacement2, $content);

        return $content;
    }
}
