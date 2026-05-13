<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'accountid',
    'clientid',
    'order_number',
    'status',
    'client_docid',
    'itemid',
    'item_name',
    'item_description',
    'quantity',
    'no_of_users',
    'start_date',
    'end_date',
    'delivery_date',
])]
class Order extends Model
{
    use HasAlphaNumericId;

    protected $table = 'orders';
    protected $primaryKey = 'orderid';

    protected function idLength(): int
    {
        return 6;
    }

    public function getRouteKeyName(): string
    {
        return 'orderid';
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'delivery_date' => 'date',
            'quantity' => 'integer',
            'no_of_users' => 'integer',
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

    public function clientDocument(): BelongsTo
    {
        return $this->belongsTo(ClientDocument::class, 'client_docid', 'client_docid');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'itemid', 'itemid');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'orderid');
    }

    public function getSubtotalAttribute(): float
    {
        return 0.0;
    }

    public function getDiscountTotalAttribute(): float
    {
        return 0.0;
    }

    public function getTaxTotalAttribute(): float
    {
        return 0.0;
    }

    public function getGrandTotalAttribute(): float
    {
        return 0.0;
    }
}
