<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RenderedEvent extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'html',
        'profile_image',
        'profile_name',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }
}
