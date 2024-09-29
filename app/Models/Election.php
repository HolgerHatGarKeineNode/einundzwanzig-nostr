<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Election extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'end_time' => 'datetime',
        ];
    }
}
