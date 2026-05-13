<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'accountid',
    'fy_id',
    'clientid',
    'invoiceid',
    'received_amount',
    'type',
    'payment_date',
    'mode',
    'reference_number',
    'description',
    'status',
])]
class Payment extends Model
{
    protected $primaryKey = 'paymentid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'accountid',
        'fy_id',
        'clientid',
        'invoiceid',
        'received_amount',
        'type',
        'payment_date',
        'mode',
        'reference_number',
        'description',
        'status',
    ];
    protected $casts = [
        'payment_date' => 'date',
    ];
    public function getRouteKeyName(): string
    {
        return 'paymentid';
    }

    protected function idLength(): int
    {
        return 6;
    }

    use HasAlphaNumericId;


    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'clientid');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoiceid');
    }
}
