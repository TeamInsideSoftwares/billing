<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'accountid',
    'clientid',
    'orderid',
    'action_type',
    'field_name',
    'old_value',
    'new_value',
    'description',
    'created_by',
])]
class OrderTimeline extends Model
{
    use HasAlphaNumericId;

    protected $table = 'order_timeline';

    protected $primaryKey = 'timelineid';

    protected function idLength(): int
    {
        return 6;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'orderid', 'orderid');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'clientid', 'clientid');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'userid');
    }
}
