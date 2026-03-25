<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'account_id',
    'client_id',
    'service_id',
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
        return $this->belongsTo(Account::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
