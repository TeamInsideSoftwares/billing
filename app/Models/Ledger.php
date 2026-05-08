<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'accountid',
    'clientid',
    'date',
    'reference_number',
    'amount',
    'type',
    'description',
])]
class Ledger extends Model
{
    use HasAlphaNumericId;

    protected $table = 'ledger';
    protected $primaryKey = 'ledgerid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'accountid',
        'clientid',
        'date',
        'reference_number',
        'amount',
        'type',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'ledgerid';
    }

    protected function idLength(): int
    {
        return 6;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'clientid', 'clientid');
    }
}
