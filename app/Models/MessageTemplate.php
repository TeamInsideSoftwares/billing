<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'accountid',
    'template_type',
    'channel',
    'name',
    'template_id',
    'meta_template_id',
    'sender_id',
    'subject',
    'body',
    'is_active',
])]
class MessageTemplate extends Model
{
    use HasAlphaNumericId;

    protected $table = 'account_templates';
    protected $primaryKey = 'templateid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }
}
