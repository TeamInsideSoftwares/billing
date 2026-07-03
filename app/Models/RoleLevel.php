<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasAlphaNumericId;

class RoleLevel extends Model
{
    use HasFactory, HasAlphaNumericId;

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
        'status'
    ];
}
