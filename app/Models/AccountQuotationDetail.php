<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use App\Models\Concerns\HasSerialNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountQuotationDetail extends Model
{
    use HasAlphaNumericId, HasSerialNumber;

    protected $primaryKey = 'account_qdid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6;
    }

    protected $fillable = [
        'account_qdid',
        'accountid',
        'serial_number',
        'prefix',
        'suffix',
        'serial_mode',
        'alphanumeric_length',
        'auto_increment_start',
        'reset_on_fy',
        'quotation_name',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'gstin',
        'tin',
        'authorize_signatory',
        'signature_upload',
        'billing_from_email',
        'terms_conditions',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }
}

