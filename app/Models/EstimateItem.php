<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'estimateid',
    'serviceid',
    'item_name',
    'item_description',
    'quantity',
    'unit_price',
    'tax_rate',
    'line_total',
    'sort_order',
])]
class EstimateItem extends Model
{
protected $primaryKey = 'estimateitemid';

    protected function idLength(): int
    {
        return 6;
    }

    use HasAlphaNumericId;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class, 'estimateid');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'serviceid');
    }
}
