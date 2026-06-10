<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'contactid',
    'accountid',
    'clientid',
    'name',
    'phone',
    'email',
    'designation',
    'is_primary',
])]
class ClientContact extends Model
{
    use HasAlphaNumericId;

    protected $primaryKey = 'contactid';

    protected $fillable = [
        'contactid',
        'accountid',
        'clientid',
        'name',
        'phone',
        'email',
        'designation',
        'is_primary',
    ];

    protected function idLength(): int
    {
        return 6;
    }

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'contactid';
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'clientid', 'clientid');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }
}
