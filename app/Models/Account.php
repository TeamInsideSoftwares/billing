<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Authenticatable
{
    use HasAlphaNumericId, HasFactory, Notifiable;
    // 1. Tell Laravel the actual name of your primary key
    protected $primaryKey = 'accountid';

    // 2. Tell Laravel this is NOT an auto-incrementing integer
    public $incrementing = false;

    // 3. Tell Laravel the key type is a string
    protected $keyType = 'string';

    protected $fillable = [
        'accountid',
        'name',
        'slug',
        'status',
        'legal_name',
        'email',
        'password',
        'phone',
        'tax_number',
        'website',
        'currency_code',
        'timezone',
        'fy_startdate',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'logo_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function financialYears(): HasMany
    {
        return $this->hasMany(FinancialYear::class, 'accountid', 'accountid');
    }
}
