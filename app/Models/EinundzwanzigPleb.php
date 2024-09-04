<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EinundzwanzigPleb extends Model
{

    protected $guarded = [];

    public function profile()
    {
        return $this->hasOne(Profile::class, 'pubkey', 'pubkey');
    }

}
