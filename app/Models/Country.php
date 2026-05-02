<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $connection = 'einundzwanzig';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'code',
        'language_codes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'language_codes' => 'array',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
