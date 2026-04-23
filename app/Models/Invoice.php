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
    'orderid',
    'invoice_number',
    'pi_number',
    'ti_number',
    'invoice_title',
    'status',
    'issue_date',
    'due_date',
    'notes',
    'terms',
    'created_by',
])]
class Invoice extends Model
{
    use HasAlphaNumericId;

    protected $table = 'invoices';
    protected $primaryKey = 'invoiceid';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    public function getRouteKeyName(): string
    {
        return 'invoiceid';
    }

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'orderid');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoiceid', 'invoiceid');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoiceid', 'invoiceid');
    }

    public function hasPaymentsRecorded(): bool
    {
        return (float) ($this->amount_paid ?? 0) > 0 || $this->payments()->exists();
    }

    public function getInvoiceNumberAttribute(): string
    {
        return (string) ($this->pi_number ?? $this->ti_number ?? '');
    }

    public function setInvoiceNumberAttribute(mixed $value): void
    {
        $this->attributes['pi_number'] = $value;
    }

    public function getInvoiceForAttribute(): string
    {
        return $this->orderid ? 'orders' : 'without_orders';
    }

    public function getCurrencyCodeAttribute(): string
    {
        return $this->client?->currency ?? 'INR';
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

    public function getAmountPaidAttribute(): float
    {
        return (float) ($this->payments()->sum('amount') ?? 0);
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, $this->grand_total - $this->amount_paid);
    }
}
