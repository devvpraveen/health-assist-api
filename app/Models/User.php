<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $tenant_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string $status
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['uuid', 'tenant_id', 'name', 'email', 'phone', 'password', 'status', 'last_login_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $attributes = [
        'status' => 'active',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
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
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot(['tenant_id', 'branch_id', 'branch_key'])
            ->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->tenant_id === null && $this->hasRole('super_admin');
    }

    public function hasRole(string $slug): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(fn (Role $role): bool => $role->slug === $slug);
        }

        return $this->roles()->where('roles.slug', $slug)->exists();
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(
                fn (Role $role): bool => $role->relationLoaded('permissions')
                    ? $role->permissions->contains(fn (Permission $permission): bool => $permission->slug === $slug)
                    : $role->permissions()->where('permissions.slug', $slug)->exists()
            );
        }

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('permissions.slug', $slug))
            ->exists();
    }

    public function ownsPatient(Patient $patient): bool
    {
        return $patient->user_id !== null && (int) $patient->user_id === (int) $this->id;
    }

    public function hasLinkedPatient(): bool
    {
        return Patient::query()->where('user_id', $this->id)->exists();
    }
}
