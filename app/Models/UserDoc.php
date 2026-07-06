<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected $appends = ['full_url'];

    protected function fullUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $localPath = public_path('storage/'.$this->doc_path);
                if (file_exists($localPath)) {
                    return asset('storage/'.$this->doc_path);
                }

                return env('TEAM_URL')
                    ? rtrim(env('TEAM_URL'), '/').'/public/storage/'.$this->doc_path
                    : asset('storage/'.$this->doc_path);
            }
        );
    }

    protected function idLength(): int
    {
        return 6;
    }

    public function userProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'profileid', 'profileid');
    }
}
