<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Election extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'end_time' => 'datetime',
            'candidates' => 'array',
        ];
    }
}
