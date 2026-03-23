<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'pubkey',
        'name',
        'display_name',
        'picture',
        'banner',
        'website',
        'about',
        'nip05',
        'lud16',
        'lud06',
        'deleted',
    ];
}
