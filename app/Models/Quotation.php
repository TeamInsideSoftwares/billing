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
    'quo_number',
    'quotation_number',
    'quo_title',
    'status',
    'issue_date',
    'due_date',
    'notes',
    'terms',
    'created_by',
])]
class Quotation extends Model
{
    use HasAlphaNumericId;

    protected $table = 'quotations';

    protected $primaryKey = 'quotationid';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    public function getRouteKeyName(): string
    {
        return 'quotationid';
    }

    protected function idLength(): int
    {
        return 6;
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class, 'quotationid', 'quotationid')
            ->orderBy('sequence')
            ->orderBy('created_at')
            ->orderBy('quo_itemid');
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

    public function getQuotationNumberAttribute(): string
    {
        return (string) ($this->quo_number ?? '');
    }

    public function setQuotationNumberAttribute(mixed $value): void
    {
        $this->attributes['quo_number'] = $value;
    }

    public function getInvoiceNumberAttribute(): string
    {
        return (string) ($this->quo_number ?? '');
    }

    public function setInvoiceNumberAttribute(mixed $value): void
    {
        $this->attributes['quo_number'] = $value;
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
        return (float) floor((float) $this->items->sum(function ($item) {
            $lineTotal = (float) ($item->line_total ?? 0);
            $discountPercent = max(0, min(100, (float) ($item->discount_percent ?? 0)));

            return max(0, $lineTotal * ($discountPercent / 100));
        }));
    }

    public function getTaxTotalAttribute(): float
    {
        return (float) ceil((float) $this->items->sum(function ($item) {
            $lineTotal = (float) ($item->line_total ?? 0);
            $discountPercent = max(0, min(100, (float) ($item->discount_percent ?? 0)));
            $taxableAmount = max(0, $lineTotal - ($lineTotal * $discountPercent / 100));
            $rate = (float) ($item->tax_rate ?? 0);

            return ceil($taxableAmount * ($rate / 100));
        }));
    }

    public function getGrandTotalAttribute(): float
    {
        return max(0, $this->subtotal - $this->discount_total + $this->tax_total);
    }
}
