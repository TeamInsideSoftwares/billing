<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'profileid',
    'accountid',
    'userid',
    'address',
    'city',
    'state',
    'country',
    'zip_code',
    'bank_name',
    'account_name',
    'account_number',
    'routing_code',
    'bank_branch',
    'status',
    'reviewed_by',
])]
class UserProfile extends Model
{
    use HasAlphaNumericId;

    protected $table = 'users_profile';

    protected $primaryKey = 'profileid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid', 'userid');
    }

    public function documents()
    {
        return $this->hasMany(UserDoc::class, 'profileid', 'profileid');
    }
}
