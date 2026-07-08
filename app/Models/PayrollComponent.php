<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollComponent extends Model
{
    use HasAlphaNumericId, HasFactory;

    // protected $connection = 'billing';
    protected $table = 'payroll_components';

    protected $primaryKey = 'componentid';

    protected $fillable = [
        'componentid',
        'accountid',
        'name',
        'description',
        'category_type',
        'type',
        'calculation_type',
        'calculation_value',
        'status',
    ];

    protected function idLength(): int
    {
        return 6;
    }
}
