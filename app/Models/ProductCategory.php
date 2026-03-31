<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCategory extends Model
{
    protected $table = 'ps_categories';

    protected $primaryKey = 'ps_catid';

    public function getRouteKeyName(): string
    {
        return 'ps_catid';
    }

    protected function idLength(): int
    {
        return 6;
    }

    use HasAlphaNumericId;

    protected $fillable = [
        'accountid',
        'name',
        'sequence',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'status' => 'string',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

