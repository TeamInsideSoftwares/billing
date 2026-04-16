<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceAddonCosting extends Model
{
    use HasAlphaNumericId;

    protected $table = 'service_addon_costings';
    protected $primaryKey = 'addon_cid';

    protected function idLength(): int
    {
        return 6;
    }

    protected $fillable = [
        'accountid',
        'addonid',
        'currency_code',
        'cost_price',
        'selling_price',
        'sac_code',
        'tax_rate',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
    ];

    public function addon(): BelongsTo
    {
        return $this->belongsTo(ServiceAddon::class, 'addonid', 'addonid');
    }
}
