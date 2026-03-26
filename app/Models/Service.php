<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'accountid',

    'service_code',
    'name',
    'description',
    'billing_type',
    'unit_price',
    'tax_rate',
    'is_active',
])]
class Service extends Model
{
protected $primaryKey = 'serviceid';
    public function getRouteKeyName(): string
    {
        return 'serviceid';
    }

    protected function idLength(): int
    {
        return 6;
    }

    use HasAlphaNumericId;

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'serviceid');
    }

    public function estimateItems(): HasMany
    {
        return $this->hasMany(EstimateItem::class, 'serviceid');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'serviceid');
    }

}
