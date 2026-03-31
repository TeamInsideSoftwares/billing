<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialYear extends Model
{
    use HasAlphaNumericId;

    protected $table = 'financial_year';
    protected $primaryKey = 'fy_id';

    public function getRouteKeyName(): string
    {
        return 'fy_id';
    }

    protected function idLength(): int
    {
        return 6;
    }

    protected $fillable = [
        'accountid',
        'financial_year',
        'default',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid');
    }
}
