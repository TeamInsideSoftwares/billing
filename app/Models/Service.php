<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'accountid',
    'ps_catid',
    'service_code',
    'type',
    'sync',
    'name',
    'sequence',
    'description',
    'addons',
    'is_active',
])]
class Service extends Model
{
    protected $table = 'items';
    protected $primaryKey = 'itemid';

    use HasAlphaNumericId;

    public function getRouteKeyName(): string
    {
        return 'itemid';
    }

    protected function idLength(): int
    {
        return 6;
    }

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'addons' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'ps_catid', 'ps_catid');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'itemid', 'itemid');
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class, 'itemid', 'itemid');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'itemid', 'itemid');
    }

    public function costings(): HasMany
    {
        return $this->hasMany(ServiceCosting::class, 'itemid', 'itemid')->orderBy('currency_code');
    }

    public function addonsLegacy(): HasMany
    {
        return $this->hasMany(ServiceAddon::class, 'itemid', 'itemid')->orderBy('sequence')->orderBy('name');
    }
}
