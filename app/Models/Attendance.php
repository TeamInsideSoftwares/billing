<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasAlphaNumericId;

    protected $connection = 'team';

    protected $table = 'attendances';

    protected $primaryKey = 'attendanceid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function idLength(): int
    {
        return 6;
    }

    protected $fillable = [
        'attendanceid',
        'accountid',
        'userid',
        'date',
        'check_in',
        'check_out',
        'shiftid',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userid', 'userid');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shiftid', 'shiftid');
    }
}
