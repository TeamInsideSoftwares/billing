<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLeavePolicy extends Model
{
    use HasAlphaNumericId;

    protected $connection = 'team';

    protected $table = 'user_leavepolicy';

    protected $primaryKey = 'policyid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6;
    }

    protected $fillable = [
        'policyid',
        'accountid',
        'userid',
        'typeid',
        'leave_per_month',
        'carry_forward',
        'probation_months',
    ];

    protected $casts = [
        'leave_per_month' => 'decimal:2',
        'carry_forward' => 'boolean',
        'probation_months' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid', 'userid');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'typeid', 'typeid');
    }
}
