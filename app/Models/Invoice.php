<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

#[Fillable([
    'accountid',
    'fy_id',
    'clientid',
    'orderid',
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

    public function getTermsAttribute($value): array
    {
        if (empty($value)) {
            return [];
        }

        // If it's already an array (thanks to casting), return it
        if (is_array($value)) {
            return $value;
        }

        // If it's a string, try to decode it
        $decoded = json_decode($value, true);
        
        // Handle double encoding: if decoded value is still a string, decode again
        if (is_string($decoded)) {
            $secondDecoded = json_decode($decoded, true);
            if (is_array($secondDecoded)) {
                return $secondDecoded;
            }
        }

        return is_array($decoded) ? $decoded : [];
    }

    public function setTermsAttribute($value): void
    {
        $this->attributes['terms'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getInvoiceNumberAttribute(): string
    {
        return (string) (!empty($this->ti_number) ? $this->ti_number : ($this->pi_number ?? ''));
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
        static $paymentAmountColumn = null;

        if ($paymentAmountColumn === null) {
            if (Schema::hasColumn('payments', 'amount')) {
                $paymentAmountColumn = 'amount';
            } elseif (Schema::hasColumn('payments', 'credit')) {
                $paymentAmountColumn = 'credit';
            } else {
                $paymentAmountColumn = false;
            }
        }

        if ($paymentAmountColumn === false) {
            return 0.0;
        }

        return (float) ($this->payments()->sum($paymentAmountColumn) ?? 0);
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, $this->grand_total - $this->amount_paid);
    }
}
