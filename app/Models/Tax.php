<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Tax extends Model
{
    protected $table = 'account_taxes';
    protected $primaryKey = 'taxid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'taxid',
        'accountid',
        'tax_name',
        'rate',
        'type',
        'description',
        'sequence',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rate' => 'float',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->taxid = 'TAX' . strtoupper(Str::random(3));
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }
}
