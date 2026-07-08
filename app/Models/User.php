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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'userid',
    'accountid',
    'name',
    'email',
    'profile_image',
    'date_of_birth',
    'salaryid',
    'depid',
    'phone',
    'designation',
    'gender',
    'notes',
    'shiftid',
    'paid_leaves_pm',
    'carry_forward',
    'probation_months',
    'password',
    'roleid',
    'permissions',
    'is_active',
    'can_assign_clients',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasAlphaNumericId, HasFactory, Notifiable;

    protected $connection = 'mysql';

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
            'can_assign_clients' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountid', 'accountid');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(AccountRole::class, 'roleid', 'roleid');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'userid', 'userid');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(AccountDepartment::class, 'depid', 'depid');
    }

    public function clients()
    {
        return $this->belongsToMany(ClientDetail::class, 'client_assignments', 'userid', 'clientid');
    }

    public function salary(): BelongsTo
    {
        return $this->belongsTo(UserSalary::class, 'salaryid', 'salaryid');
    }

    public function policies()
    {
        return $this->hasMany(UserPolicy::class, 'userid', 'userid');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shiftid', 'shiftid');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'userid', 'userid');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'userid', 'userid');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function hasPermission(string $permission): bool
    {
        $perms = $this->permissions ?? [];

        return in_array($permission, $perms, true);
    }

    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_assignments', 'userid', 'assigned_userid')
                    ->withPivot('team_name')
                    ->withTimestamps();
    }

    /**
     * Returns the list of userids this user is allowed to manage in Team Work.
     *
     * @return array<int, string>
     */
    public function assignedUserIds(): array
    {
        return $this->teamMembers()->pluck('account_users.userid')->toArray();
    }
}
