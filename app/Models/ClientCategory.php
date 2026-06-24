<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'accountid',
    'name',
    'sequence',
])]
class ClientCategory extends Model
{
    protected $table = 'client_categories';

    protected $primaryKey = 'categoryid';

    public function getRouteKeyName(): string
    {
        return 'categoryid';
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
        return $this->hasMany(Client::class, 'categoryid');
    }
}
