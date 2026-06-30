<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $table = 'leave_types';

    protected $primaryKey = 'typeid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'typeid',
        'accountid',
        'name',
        'description',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->typeid)) {
                $model->typeid = strtoupper(substr(uniqid(), -6));
            }
        });
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }
}
