<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Model;

class AccountDepartment extends Model
{
    use HasAlphaNumericId;

    protected $primaryKey = 'depid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6; // Example length
    }

    protected $fillable = [
        'depid',
        'accountid',
        'name',
        'status',
    ];
}
