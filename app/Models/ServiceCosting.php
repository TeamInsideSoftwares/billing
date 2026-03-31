<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'accountid',
    'serviceid',
    'currency_code',
    'cost_price',
    'selling_price',
    'sac_code',
    'tax_rate',
])]
class ServiceCosting extends Model
{
    use HasAlphaNumericId;

    protected $primaryKey = 'costingid';

    protected function idLength(): int
    {
        return 8;
    }

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'serviceid', 'serviceid');
    }
}
