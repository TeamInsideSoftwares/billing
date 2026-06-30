<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'profileid',
    'doc_type',
    'doc_path',
])]
class UserDoc extends Model
{
    use HasAlphaNumericId;

    protected $table = 'users_doc';

    protected $primaryKey = 'docid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6;
    }

    public function userProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'profileid', 'profileid');
    }
}
