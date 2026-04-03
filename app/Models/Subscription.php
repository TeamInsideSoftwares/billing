<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'accountid',
    'clientid',
    'itemid',
    'start_date',
    'next_billing_date',
    'end_date',
    'billing_cycle',
    'price',
    'quantity',
    'status',
    'auto_generate_invoice',
    'created_by',
])]
class Subscription extends Model
{
    protected $primaryKey = 'subscriptionid';

    public function getRouteKeyName(): string
    {
        return 'subscriptionid';
    }

    protected function idLength(): int
    {
        return 6;
    }

    use HasAlphaNumericId;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'next_billing_date' => 'date',
            'end_date' => 'date',
            'price' => 'decimal:2',
            'quantity' => 'decimal:2',
            'auto_generate_invoice' => 'boolean',
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

    public function item(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'itemid', 'itemid');
    }

    public function service(): BelongsTo
    {
        return $this->item();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
