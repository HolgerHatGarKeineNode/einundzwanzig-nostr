<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{

    protected $guarded = [];

    public function renderedEvent()
    {
        return $this->hasOne(RenderedEvent::class, 'event_id', 'event_id');
    }

}
