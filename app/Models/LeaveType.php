<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasAlphaNumericId;
    use HasFactory;

    protected $connection = 'mysql';

    protected $primaryKey = 'typeid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6;
    }

    protected $fillable = [
        'accountid',
        'name',
        'description',
        'status',
    ];
}
