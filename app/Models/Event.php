<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'pubkey',
        'parent_event_id',
        'json',
        'type',
    ];

    public function renderedEvent()
    {
        return $this->hasOne(RenderedEvent::class, 'event_id', 'event_id');
    }
}
