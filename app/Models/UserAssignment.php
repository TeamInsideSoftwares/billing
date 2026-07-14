<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;

class UserAssignment extends Pivot
{
    protected $table = 'user_assignments';

    protected $primaryKey = 'user_assignid';

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->user_assignid)) {
                $model->user_assignid = Str::random(6);
            }
        });
    }
}
