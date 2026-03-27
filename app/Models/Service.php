<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'accountid',
    'product_categoryid',

    'service_code',
    'name',
    'description',
    'cost_price',
    'selling_price',
    'sac_code',
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
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_categoryid', 'product_categoryid');
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
