<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleLevel extends Model
{
    use HasAlphaNumericId, HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6;
    }

    protected $table = 'roles_level';

    protected $primaryKey = 'levelid';

    protected $fillable = [
        'level_name',
        'level_value',
        'status',
    ];
}
