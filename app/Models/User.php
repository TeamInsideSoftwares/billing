<?php

namespace App\Models;

use App\Models\Concerns\HasAlphaNumericId;
use App\Notifications\ResetPasswordNotification;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable([
    'userid',
    'accountid',
    'name',
    'email',
    'profile_image',
    'department',
    'phone',
    'designation',
    'notes',
    'password',
    'role',
    'permissions',
    'is_active',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasAlphaNumericId, HasFactory, Notifiable;

    protected $table = 'account_users';

    protected $primaryKey = 'userid';

    protected function idLength(): int
    {
        return 6;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'permissions' => 'array',
            'password' => 'hashed',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }

    public function getSlugAttribute(): string
    {
        $accountSlug = (string) ($this->account?->slug ?? '');
        if ($accountSlug !== '') {
            return $accountSlug;
        }

        $base = trim((string) ($this->name ?? ''));
        if ($base === '') {
            $base = (string) ($this->email ?? 'account-user');
        }

        return (string) Str::of($base)->slug('-');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
