<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasAlphaNumericId;

    protected $primaryKey = 'shiftid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6;
    }

    protected $fillable = [
        'accountid',
        'shift_name',
        'start_time',
        'end_time',
        'break_duration',
        'break_start_time',
        'break_end_time',
        'break_grace_period',
        'status',
    ];
}
