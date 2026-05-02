<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\Meetup;
use App\Models\MeetupEvent;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class MeetupSeeder extends Seeder
{
    public function run(): void
    {
        $de = Country::factory()->germany()->create();
        $at = Country::factory()->austria()->create();
        $ch = Country::factory()->switzerland()->create();

        $vienna = City::factory()->vienna()->for($at)->create();
        $berlin = City::factory()->berlin()->for($de)->create();
        $munich = City::factory()->munich()->for($de)->create();
        $zurich = City::factory()->zurich()->for($ch)->create();

        $viennaMeetup = Meetup::factory()->vienna()->for($vienna)->create();
        $berlinMeetup = Meetup::factory()->berlin()->for($berlin)->create();
        $munichMeetup = Meetup::factory()->state(['name' => 'Einundzwanzig München'])->for($munich)->create();
        $zurichMeetup = Meetup::factory()->state(['name' => 'Einundzwanzig Zürich'])->for($zurich)->create();

        Venue::factory()->bitcoinBarVienna()->for($vienna)->create();
        Venue::factory()->count(2)->for($berlin)->create();
        Venue::factory()->count(2)->for($munich)->create();
        Venue::factory()->count(1)->for($zurich)->create();

        foreach ([$viennaMeetup, $berlinMeetup, $munichMeetup, $zurichMeetup] as $meetup) {
            MeetupEvent::factory()->past()->for($meetup)->count(2)->create();
            MeetupEvent::factory()->upcoming()->for($meetup)->count(2)->create();
        }
    }
}
