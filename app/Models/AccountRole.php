<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Model;

class AccountRole extends Model
{
    use HasAlphaNumericId;

    protected $primaryKey = 'roleid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6; // Example length
    }

    protected $fillable = [
        'roleid',
        'accountid',
        'name',
        'status',
    ];
}
