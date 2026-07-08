<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountPolicy extends Model
{
    use HasAlphaNumericId, HasFactory;

    // protected $connection = 'billing';
    protected $table = 'account_policies';

    protected $primaryKey = 'policyid';

    protected $fillable = [
        'policyid',
        'accountid',
        'componentid',
        'title',
        'description',
        'rules',
        'status',
    ];

    protected $casts = [
        'rules' => 'array',
        'status' => 'boolean',
    ];

    protected function idLength(): int
    {
        return 6;
    }

    public function component()
    {
        return $this->belongsTo(PayrollComponent::class, 'componentid', 'componentid');
    }
}
