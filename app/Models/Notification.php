<?php

namespace App\Models;

use App\Enums\NewsCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Notification extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'category' => NewsCategory::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('pdf')
            ->acceptsMimeTypes(['application/pdf'])
            ->singleFile()
            ->useDisk('private');
    }

    public function einundzwanzigPleb(): BelongsTo
    {
        return $this->belongsTo(EinundzwanzigPleb::class);
    }
}
