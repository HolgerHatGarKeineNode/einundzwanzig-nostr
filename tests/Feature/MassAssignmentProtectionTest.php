<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Course;
use App\Models\CourseEvent;
use App\Models\EinundzwanzigPleb;
use App\Models\Election;
use App\Models\Event;
use App\Models\Lecturer;
use App\Models\Meetup;
use App\Models\MeetupEvent;
use App\Models\Notification;
use App\Models\PaymentEvent;
use App\Models\Profile;
use App\Models\ProjectProposal;
use App\Models\RenderedEvent;
use App\Models\Venue;
use App\Models\Vote;
use Illuminate\Database\Eloquent\MassAssignmentException;

it('ensures no model uses guarded empty array', function () {
    $models = [
        PaymentEvent::class,
        EinundzwanzigPleb::class,
        Vote::class,
        ProjectProposal::class,
        Election::class,
        Venue::class,
        MeetupEvent::class,
        CourseEvent::class,
        Course::class,
        Meetup::class,
        Lecturer::class,
        City::class,
        Event::class,
        RenderedEvent::class,
        Profile::class,
        Category::class,
        Country::class,
        Notification::class,
    ];

    foreach ($models as $modelClass) {
        $reflection = new ReflectionClass($modelClass);
        $property = $reflection->getProperty('guarded');
        $instance = $reflection->newInstanceWithoutConstructor();
        $guarded = $property->getValue($instance);

        expect($guarded)
            ->not->toBe([], "{$modelClass} still uses \$guarded = [] which is insecure");
    }
});

it('ensures all models have explicit fillable arrays', function () {
    $models = [
        PaymentEvent::class,
        EinundzwanzigPleb::class,
        Vote::class,
        ProjectProposal::class,
        Election::class,
        Venue::class,
        MeetupEvent::class,
        CourseEvent::class,
        Course::class,
        Meetup::class,
        Lecturer::class,
        City::class,
        Event::class,
        RenderedEvent::class,
        Profile::class,
        Category::class,
        Country::class,
        Notification::class,
    ];

    foreach ($models as $modelClass) {
        $reflection = new ReflectionClass($modelClass);
        $property = $reflection->getProperty('fillable');
        $instance = $reflection->newInstanceWithoutConstructor();

        expect($property->getValue($instance))
            ->toBeArray("{$modelClass} should have an explicit \$fillable array");
    }
});

it('blocks mass assignment of einundzwanzig_pleb_id on PaymentEvent', function () {
    $paymentEvent = new PaymentEvent;
    $paymentEvent->fill(['einundzwanzig_pleb_id' => 999]);

    expect($paymentEvent->einundzwanzig_pleb_id)->toBeNull();
});

it('verifies EinundzwanzigPleb fillable does not contain application_for', function () {
    $reflection = new ReflectionClass(EinundzwanzigPleb::class);
    $property = $reflection->getProperty('fillable');
    $instance = $reflection->newInstanceWithoutConstructor();
    $fillable = $property->getValue($instance);

    expect($fillable)->not->toContain('application_for');
    expect($fillable)->not->toContain('id');
    expect($fillable)->toContain('npub');
    expect($fillable)->toContain('pubkey');
    expect($fillable)->toContain('email');
    expect($fillable)->toContain('no_email');
    expect($fillable)->toContain('nip05_handle');
});

it('blocks mass assignment of accepted and sats_paid on ProjectProposal', function () {
    $proposal = new ProjectProposal;
    $proposal->fill([
        'name' => 'Test',
        'accepted' => true,
        'sats_paid' => 100000,
        'einundzwanzig_pleb_id' => 1,
        'slug' => 'injected-slug',
    ]);

    expect($proposal->accepted)->toBeNull();
    expect($proposal->sats_paid)->toBeNull();
    expect($proposal->einundzwanzig_pleb_id)->toBeNull();
    expect($proposal->slug)->toBeNull();
    expect($proposal->name)->toBe('Test');
});

it('blocks mass assignment of all fields on Election', function () {
    $election = new Election;

    expect(fn () => $election->fill(['year' => 2025]))
        ->toThrow(MassAssignmentException::class);
});

it('blocks mass assignment of created_by and slug on Venue', function () {
    $venue = new Venue;
    $venue->fill([
        'name' => 'Test Venue',
        'created_by' => 999,
        'slug' => 'injected-slug',
    ]);

    expect($venue->name)->toBe('Test Venue');
    expect($venue->created_by)->toBeNull();
    expect($venue->slug)->toBeNull();
});

it('blocks mass assignment of meetup_id and created_by on MeetupEvent', function () {
    $event = new MeetupEvent;
    $event->fill([
        'start' => '2025-01-01',
        'meetup_id' => 999,
        'created_by' => 999,
        'attendees' => ['a'],
    ]);

    expect($event->start)->not->toBeNull();
    expect($event->meetup_id)->toBeNull();
    expect($event->created_by)->toBeNull();
    expect($event->attendees)->toBeNull();
});

it('blocks mass assignment of course_id venue_id and created_by on CourseEvent', function () {
    $event = new CourseEvent;
    $event->fill([
        'from' => '2025-01-01',
        'to' => '2025-01-02',
        'course_id' => 999,
        'venue_id' => 999,
        'created_by' => 999,
    ]);

    expect($event->from)->not->toBeNull();
    expect($event->to)->not->toBeNull();
    expect($event->course_id)->toBeNull();
    expect($event->venue_id)->toBeNull();
    expect($event->created_by)->toBeNull();
});

it('blocks mass assignment of lecturer_id and created_by on Course', function () {
    $course = new Course;
    $course->fill([
        'name' => 'Test Course',
        'description' => 'Test',
        'lecturer_id' => 999,
        'created_by' => 999,
    ]);

    expect($course->name)->toBe('Test Course');
    expect($course->description)->toBe('Test');
    expect($course->lecturer_id)->toBeNull();
    expect($course->created_by)->toBeNull();
});

it('blocks mass assignment of city_id created_by and slug on Meetup', function () {
    $meetup = new Meetup;
    $meetup->fill([
        'name' => 'Test Meetup',
        'city_id' => 999,
        'created_by' => 999,
        'slug' => 'injected',
        'github_data' => '{}',
        'simplified_geojson' => '{}',
    ]);

    expect($meetup->name)->toBe('Test Meetup');
    expect($meetup->city_id)->toBeNull();
    expect($meetup->created_by)->toBeNull();
    expect($meetup->slug)->toBeNull();
});

it('blocks mass assignment of active created_by and slug on Lecturer', function () {
    $lecturer = new Lecturer;
    $lecturer->fill([
        'name' => 'Test Lecturer',
        'active' => true,
        'created_by' => 999,
        'slug' => 'injected',
    ]);

    expect($lecturer->name)->toBe('Test Lecturer');
    expect($lecturer->active)->toBeNull();
    expect($lecturer->created_by)->toBeNull();
    expect($lecturer->slug)->toBeNull();
});

it('blocks mass assignment of country_id created_by and slug on City', function () {
    $city = new City;
    $city->fill([
        'name' => 'Test City',
        'country_id' => 999,
        'created_by' => 999,
        'slug' => 'injected',
        'osm_relation' => '{}',
        'simplified_geojson' => '{}',
    ]);

    expect($city->name)->toBe('Test City');
    expect($city->country_id)->toBeNull();
    expect($city->created_by)->toBeNull();
    expect($city->slug)->toBeNull();
});

it('blocks mass assignment of einundzwanzig_pleb_id and category on Notification', function () {
    $notification = new Notification;
    $notification->fill([
        'name' => 'Test News',
        'description' => 'Test',
        'einundzwanzig_pleb_id' => 999,
        'category' => 1,
    ]);

    expect($notification->name)->toBe('Test News');
    expect($notification->description)->toBe('Test');
    expect($notification->einundzwanzig_pleb_id)->toBeNull();
    expect($notification->category)->toBeNull();
});

it('blocks mass assignment of code and language_codes on Country', function () {
    $country = new Country;
    $country->fill([
        'name' => 'Test',
        'code' => 'XX',
        'language_codes' => ['en'],
    ]);

    expect($country->name)->toBe('Test');
    expect($country->code)->toBeNull();
    expect($country->language_codes)->toBeNull();
});

it('allows fillable fields on PaymentEvent', function () {
    $paymentEvent = new PaymentEvent;
    $paymentEvent->fill([
        'year' => 2025,
        'event_id' => 'test-event',
        'amount' => 21000,
        'paid' => true,
        'btc_pay_invoice' => 'inv-123',
    ]);

    expect($paymentEvent->year)->toBe(2025);
    expect($paymentEvent->event_id)->toBe('test-event');
    expect($paymentEvent->amount)->toBe(21000);
    expect($paymentEvent->paid)->toBeTrue();
    expect($paymentEvent->btc_pay_invoice)->toBe('inv-123');
});
