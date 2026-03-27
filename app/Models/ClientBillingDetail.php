<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientBillingDetail extends Model
{
    protected $fillable = [
        'clientid',
        'gstin',
        'billing_email',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'clientid', 'clientid');
    }
}
