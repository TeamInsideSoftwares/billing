<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'accountid',
    'setting_key',
    'setting_value',
])]
class Setting extends Model
{
protected $primaryKey = 'settingid';
    public function getRouteKeyName(): string
    {
        return 'settingid';
    }

    protected function idLength(): int
    {
        return 6;
    }

    use HasAlphaNumericId;

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid');
    }

    // Accessors for shorter property names in views
    public function getKeyAttribute(): string
    {
        return $this->setting_key;
    }

    public function getValueAttribute(): ?string
    {
        return $this->setting_value;
    }
}
