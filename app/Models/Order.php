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
    'order_title',
    'status',
    'order_date',
    'delivery_date',
    'duration',
    'frequency',
    'no_of_users',
    'subtotal',
    'grand_total',
    'notes',
    'terms',
    'created_by',
    'sales_person_id',
])]
class Order extends Model
{
    protected $primaryKey = 'orderid';
    
    public function getRouteKeyName(): string
    {
        return 'orderid';
    }

    protected function idLength(): int
    {
        return 6;
    }

    use HasAlphaNumericId;

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'delivery_date' => 'date',
            'no_of_users' => 'integer',
            'subtotal' => 'decimal:2',
            'grand_total' => 'decimal:2',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_person_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'orderid');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'orderid');
    }

    public function proformaInvoices(): HasMany
    {
        return $this->hasMany(ProformaInvoice::class, 'orderid');
    }
}
