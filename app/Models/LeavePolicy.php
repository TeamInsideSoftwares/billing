<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeavePolicy extends Model
{
    use HasAlphaNumericId;
    use HasFactory;

    protected $primaryKey = 'leave_policyid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6;
    }

    protected $fillable = [
        'accountid',
        'typeid',
        'policy_name',
        'description',
        'carry_forward_limit',
        'min_days_per_application',
        'max_days_per_application',
        'is_paid',
        'status',
    ];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'typeid', 'typeid');
    }
}
