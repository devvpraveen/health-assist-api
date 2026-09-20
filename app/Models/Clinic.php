<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ClinicFactory;
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
 * @property int $organization_id
 * @property int|null $primary_branch_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $logo_path
 * @property string $type
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $whatsapp_number
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property string|null $latitude
 * @property string|null $longitude
 * @property bool $is_public
 * @property string $status
 * @property int|null $experience_years
 * @property array<string, mixed>|null $meta
 * @property string $default_locale
 * @property array<int, string>|null $supported_locales
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'organization_id',
    'primary_branch_id',
    'name',
    'slug',
    'description',
    'logo_path',
    'type',
    'phone',
    'email',
    'website',
    'whatsapp_number',
    'address_line1',
    'address_line2',
    'city',
    'state',
    'postal_code',
    'country',
    'latitude',
    'longitude',
    'is_public',
    'status',
    'experience_years',
    'meta',
    'default_locale',
    'supported_locales',
])]
class Clinic extends Model
{
    /** @use HasFactory<ClinicFactory> */
    use BelongsToTenant, HasFactory;

    public const TYPES = ['clinic', 'hospital', 'diagnostic', 'wellness'];

    public const STATUSES = ['active', 'inactive', 'archived'];

    protected $attributes = [
        'type' => 'clinic',
        'is_public' => false,
        'status' => 'active',
        'default_locale' => 'en',
    ];

    protected static function booted(): void
    {
        static::creating(function (Clinic $clinic): void {
            if (empty($clinic->uuid)) {
                $clinic->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'experience_years' => 'integer',
            'meta' => 'array',
            'supported_locales' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function primaryBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'primary_branch_id');
    }

    /**
     * @return HasMany<Branch, $this>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * @return HasMany<Provider, $this>
     */
    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }

    /**
     * @return HasMany<Schedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * @return BelongsToMany<Specialty, $this>
     */
    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class)->withTimestamps();
    }
}
