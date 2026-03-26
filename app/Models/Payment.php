<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'accountid',
    'clientid',
    'invoiceid',
    'payment_number',
    'payment_date',
    'amount',
    'payment_method',
    'reference_number',
    'gateway_name',
    'gateway_transaction_id',
    'status',
    'notes',
    'received_by',
])]
class Payment extends Model
{
protected $primaryKey = 'paymentid';
    public function getRouteKeyName(): string
    {
        return 'paymentid';
    }

    protected function idLength(): int
    {
        return 6;
    }

    use HasAlphaNumericId;

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

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

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
