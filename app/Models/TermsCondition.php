<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermsCondition extends Model
{
    use HasAlphaNumericId;

    protected $table = 'terms_conditions';
    protected $primaryKey = 'tc_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6;
    }

    protected $fillable = [
        'tc_id',
        'accountid',
        'type',
        'content',
        'is_active',
        'is_default',
        'sequence',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }
}
