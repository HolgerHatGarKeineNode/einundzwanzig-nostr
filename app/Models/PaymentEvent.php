<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'year',
        'event_id',
        'amount',
        'paid',
        'btc_pay_invoice',
    ];

    public function pleb()
    {
        return $this->belongsTo(EinundzwanzigPleb::class, 'einundzwanzig_pleb_id');
    }
}
