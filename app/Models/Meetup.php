<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cookie;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Meetup extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasSlug;

    protected $connection = 'einundzwanzig';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'city_id' => 'integer',
        'github_data' => 'json',
        'simplified_geojson' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->created_by) {
                $model->created_by = auth()->id();
            }
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['name'])
            ->saveSlugsTo('slug')
            ->usingLanguage(Cookie::get('lang', config('app.locale')));
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Crop, 300, 300)
            ->nonQueued();
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 130, 130)
            ->width(130)
            ->height(130);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->useFallbackUrl(asset('img/einundzwanzig.png'));
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    protected function logoSquare(): Attribute
    {
        $media = $this->getFirstMedia('logo');
        if ($media) {
            $path = str($media->getPath())->after('storage/app/');
        } else {
            $path = 'img/einundzwanzig.png';
        }

        return Attribute::make(
            get: fn() => url()->route('img',
                [
                    'path' => $path,
                    'w' => 900,
                    'h' => 900,
                    'fit' => 'crop',
                    'fm' => 'webp',
                ]),
        );
    }

    protected function nextEvent(): Attribute
    {
        $nextEvent = $this->meetupEvents()->where('start', '>=', now())->orderBy('start')->first();

        return Attribute::make(
            get: fn() => $nextEvent ? [
                'start' => $nextEvent->start->toDateTimeString(),
                'portalLink' => url()->route('meetup.event.landing', ['country' => $this->city->country, 'meetupEvent' => $nextEvent]),
                'location' => $nextEvent->location,
                'description' => $nextEvent->description,
                'link' => $nextEvent->link,
                'attendees' => count($nextEvent->attendees ?? []),
                'nostr_note' => str($nextEvent->nostr_status)->after('Sent event ')->before(' to '),
            ] : null,
        );
    }

    public function meetupEvents(): HasMany
    {
        return $this->hasMany(MeetupEvent::class);
    }
}
