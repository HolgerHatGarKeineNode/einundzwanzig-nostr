<?php

namespace App\Models;

use App\Enums\AssociationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

class EinundzwanzigPleb extends Authenticatable implements CipherSweetEncrypted
{
    use HasFactory;
    use UsesCipherSweet;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'association_status' => AssociationStatus::class,
        ];
    }

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addOptionalTextField('email')
            ->addBlindIndex('email', new BlindIndex('email_index'));
    }

    public function profile()
    {
        return $this->hasOne(Profile::class, 'pubkey', 'pubkey');
    }

    public function paymentEvents()
    {
        return $this->hasMany(PaymentEvent::class);
    }
}
