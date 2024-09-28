<?php

namespace App\Models;

use App\Enums\AssociationStatus;
use Illuminate\Database\Eloquent\Model;

class EinundzwanzigPleb extends Model
{

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'association_status' => AssociationStatus::class,
        ];
    }

    public function profile()
    {
        return $this->hasOne(Profile::class, 'pubkey', 'pubkey');
    }

}
