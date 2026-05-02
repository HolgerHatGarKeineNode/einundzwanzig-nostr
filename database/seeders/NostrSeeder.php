<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Profile;
use App\Models\RenderedEvent;
use Illuminate\Database\Seeder;

class NostrSeeder extends Seeder
{
    public function run(): void
    {
        Profile::factory()->markusTurm()->create();

        $boardProfiles = [
            [
                'pubkey' => '0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033',
                'name' => 'pleb1',
                'display_name' => 'Vorstandsmitglied 1',
            ],
            [
                'pubkey' => '430169631f2f0682c60cebb4f902d68f0c71c498fd1711fd982f052cf1fd4279',
                'name' => 'pleb2',
                'display_name' => 'Vorstandsmitglied 2',
            ],
            [
                'pubkey' => '7acf30cf60b85c62b8f654556cc21e4016df8f5604b3b6892794f88bb80d7a1d',
                'name' => 'pleb3',
                'display_name' => 'Vorstandsmitglied 3',
            ],
            [
                'pubkey' => '19e358b8011f5f4fc653c565c6d4c2f33f32661f4f90982c9eedc292a8774ec3',
                'name' => 'pleb4',
                'display_name' => 'Vorstandsmitglied 4',
            ],
        ];

        foreach ($boardProfiles as $data) {
            Profile::query()->create([
                ...$data,
                'about' => 'Vorstand bei Einundzwanzig. Bitcoin only.',
                'nip05' => $data['name'].'@einundzwanzig.space',
                'lud16' => $data['name'].'@walletofsatoshi.com',
                'website' => 'https://einundzwanzig.space',
                'picture' => 'https://m.primal.net/'.fake()->uuid().'.jpg',
                'deleted' => false,
            ]);
        }

        Event::factory()
            ->fromMarkusTurm()
            ->count(5)
            ->create()
            ->each(function (Event $event): void {
                RenderedEvent::query()->create([
                    'event_id' => $event->event_id,
                    'html' => '<div class="prose"><p>'.fake()->paragraph().'</p></div>',
                    'profile_image' => 'https://m.primal.net/HQqf.jpg',
                    'profile_name' => 'markusturm',
                ]);
            });
    }
}
