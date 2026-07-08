<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends Model
{
    use HasAlphaNumericId, HasFactory;

    // protected $connection = 'billing';
    protected $table = 'user_policies';

    protected $primaryKey = 'user_policyid';

    protected $fillable = [
        'user_policyid',
        'accountid',
        'userid',
        'policyid',
    ];

    protected function idLength(): int
    {
        return 6;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userid', 'userid');
    }

    public function policy()
    {
        return $this->belongsTo(AccountPolicy::class, 'policyid', 'policyid');
    }
}
