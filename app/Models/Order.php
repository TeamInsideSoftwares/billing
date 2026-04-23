<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'accountid',
    'fy_id',
    'clientid',
    'order_number',
    'order_title',
    'status',
    'order_date',
    'delivery_date',
    'duration',
    'frequency',
    'no_of_users',
    'is_verified',
    'notes',
    'terms',
    'created_by',
    'sales_person_id',
    'po_number',
    'po_date',
    'po_file',
    'agreement_ref',
    'agreement_date',
    'agreement_file',
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
            'po_date' => 'date',
            'agreement_date' => 'date',
            'no_of_users' => 'integer',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid');
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class, 'fy_id', 'fy_id');
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

    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum('line_total');
    }

    public function getDiscountTotalAttribute(): float
    {
        return (float) floor((float) $this->items->sum('discount_amount'));
    }

    public function getTaxTotalAttribute(): float
    {
        return (float) $this->items->sum(function ($item) {
            $lineTotal = (float) ($item->line_total ?? 0);
            $discount = (float) ($item->discount_amount ?? 0);
            $rate = (float) ($item->tax_rate ?? 0);
            return ceil(max(0, $lineTotal - $discount) * ($rate / 100));
        });
    }

    public function getGrandTotalAttribute(): float
    {
        return max(0, $this->subtotal - $this->discount_total + $this->tax_total);
    }
}
