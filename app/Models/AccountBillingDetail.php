<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use App\Models\Concerns\HasSerialNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountBillingDetail extends Model
{
    use HasAlphaNumericId, HasSerialNumber;

    protected $primaryKey = 'account_bdid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6;
    }

    protected $fillable = [
        'account_bdid',
        'accountid',
        'serial_number',
        'prefix',
        'prefix_type',
        'prefix_value',
        'prefix_length',
        'prefix_separator',
        'suffix',
        'suffix_type',
        'suffix_value',
        'suffix_length',
        'serial_mode',
        'number_type',
        'number_value',
        'number_length',
        'number_separator',
        'alphanumeric_length',
        'auto_increment_start',
        'reset_on_fy',
        'billing_name',
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

