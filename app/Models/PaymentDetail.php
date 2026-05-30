<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'accountid',
    'clientid',
    'paymentid',
    'invoiceid',
    'received_amount',
    'tds_amount',
])]
class PaymentDetail extends Model
{
    use HasAlphaNumericId;

    protected $table = 'payment_details';
    protected $primaryKey = 'detailid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'accountid',
        'clientid',
        'paymentid',
        'invoiceid',
        'received_amount',
        'tds_amount',
    ];

    protected function idLength(): int
    {
        return 6;
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'paymentid', 'paymentid');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoiceid', 'invoiceid');
    }
}
