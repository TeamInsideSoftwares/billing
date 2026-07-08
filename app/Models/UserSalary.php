<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSalary extends Model
{
    use HasAlphaNumericId, HasFactory;

    // protected $connection = 'billing';
    protected $table = 'user_salaries';

    protected $primaryKey = 'salaryid';

    protected $fillable = [
        'salaryid',
        'accountid',
        'userid',
        'amount',
        'effective_date',
        'status',
    ];

    protected function idLength(): int
    {
        return 6;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userid', 'userid');
    }
}
