<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientBillingDetail extends Model
{
    protected $primaryKey = 'bd_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'bd_id',
        'accountid',
        'business_name',
        'gstin',
        'billing_email',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'billing_phone',
        'country',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'bd_id', 'bd_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }
}
