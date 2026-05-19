<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AccountCredential extends Authenticatable
{
    use Notifiable;

    protected $table = 'account_credentials';

    protected $fillable = [
        'accountid',
        'email',
        'password',
        'remember_token',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }

    // Keep compatibility with existing UI references (auth()->user()->name/slug).
    public function getNameAttribute(): string
    {
        return (string) ($this->account?->name ?? 'Account');
    }

    public function getSlugAttribute(): string
    {
        return (string) ($this->account?->slug ?? 'account');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
