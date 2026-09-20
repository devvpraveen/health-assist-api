<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $user_id
 * @property int $clinic_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $display_name
 * @property string $type
 * @property string|null $bio
 * @property string|null $photo_path
 * @property int|null $years_experience
 * @property array<int, string>|null $languages
 * @property string|null $license_number
 * @property string $verification_status
 * @property bool $is_public
 * @property string $status
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'user_id',
    'clinic_id',
    'first_name',
    'last_name',
    'display_name',
    'type',
    'bio',
    'photo_path',
    'years_experience',
    'languages',
    'license_number',
    'verification_status',
    'is_public',
    'status',
])]
class Provider extends Model
{
    /** @use HasFactory<ProviderFactory> */
    use BelongsToTenant, HasFactory;

    public const TYPES = ['doctor', 'physiotherapist', 'therapist', 'other'];

    public const VERIFICATION_STATUSES = ['pending', 'verified', 'rejected'];

    public const STATUSES = ['active', 'inactive', 'archived'];

    protected $attributes = [
        'type' => 'doctor',
        'verification_status' => 'pending',
        'is_public' => false,
        'status' => 'active',
    ];

    protected static function booted(): void
    {
        static::creating(function (Provider $provider): void {
            if (empty($provider->uuid)) {
                $provider->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'years_experience' => 'integer',
            'is_public' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Clinic, $this>
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * @return BelongsToMany<Specialty, $this>
     */
    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class)
            ->withPivot(['is_primary'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Branch, $this>
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'provider_branch')->withTimestamps();
    }

    /**
     * @return HasMany<Schedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
