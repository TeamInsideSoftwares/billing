<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'holidayid';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'holidayid',
        'accountid',
        'title',
        'holiday_date',
        'type',
        'is_recurring',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'holiday_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    /**
     * Get the account that owns the holiday.
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }
}
