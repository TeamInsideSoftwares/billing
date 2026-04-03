<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceAddon extends Model
{
    use HasAlphaNumericId;

    protected $table = 'service_addons';
    protected $primaryKey = 'addonid';

    protected function idLength(): int
    {
        return 6;
    }

    protected $fillable = [
        'accountid',
        'itemid',
        'addon_code',
        'name',
        'sequence',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'itemid', 'itemid');
    }

    public function costings(): HasMany
    {
        return $this->hasMany(ServiceAddonCosting::class, 'addonid', 'addonid')->orderBy('currency_code');
    }
}

