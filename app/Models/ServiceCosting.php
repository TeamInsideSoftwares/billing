<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceCosting extends Model
{
    use HasAlphaNumericId;

    protected $table = 'item_costings';
    protected $primaryKey = 'costingid';

    protected function idLength(): int
    {
        return 6;
    }

    protected $fillable = [
        'accountid',
        'itemid',
        'currency_code',
        'cost_price',
        'selling_price',
        'sac_code',
        'taxid',
        'tax_rate',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'itemid', 'itemid');
    }

    public function service(): BelongsTo
    {
        return $this->item();
    }
}
