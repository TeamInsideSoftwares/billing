<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendancePolicy extends Model
{
    use HasAlphaNumericId, HasFactory;

    protected $primaryKey = 'att_policyid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6;
    }

    protected $fillable = [
        'accountid',
        'policy_name',
        'description',
        'late_arrival_grace',
        'early_departure_grace',
        'overtime_rate',
        'status',
    ];
}
