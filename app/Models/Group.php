<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'accountid',
    'group_name',
    'email',
    'address_line_1',
    'address_line_2',
    'city',
    'state',
    'postal_code',
    'country',
    'gstin',
])]
class Group extends Model
{
    protected $primaryKey = 'groupid';
    public function getRouteKeyName(): string
    {
        return 'groupid';
    }

    protected function idLength(): int
    {
        return 6;
    }

    use HasAlphaNumericId;

    public function account()
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'groupid');
    }
}
