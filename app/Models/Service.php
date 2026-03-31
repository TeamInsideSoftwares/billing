<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use App\Models\ServiceCosting;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'accountid',
    'ps_catid',
    'service_code',
    'name',
    'sequence',
    'description',
    'is_active',
])]
class Service extends Model
{
    protected $primaryKey = 'serviceid';

    use HasAlphaNumericId;

    public function getRouteKeyName(): string
    {
        return 'serviceid';
    }

    protected function idLength(): int
    {
        return 6;
    }

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
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

    public function costings(): HasMany
    {
        return $this->hasMany(ServiceCosting::class, 'serviceid', 'serviceid')->orderBy('currency_code');
    }

}
