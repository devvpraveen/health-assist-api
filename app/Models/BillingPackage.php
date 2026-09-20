<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\BillingPackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $clinic_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $session_count
 * @property int|null $validity_days
 * @property int $price_cents
 * @property string $currency
 * @property bool $is_active
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'clinic_id',
    'name',
    'slug',
    'description',
    'session_count',
    'validity_days',
    'price_cents',
    'currency',
    'is_active',
])]
class BillingPackage extends Model
{
    /** @use HasFactory<BillingPackageFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = [
        'currency' => 'INR',
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (BillingPackage $package): void {
            if (empty($package->uuid)) {
                $package->uuid = (string) Str::uuid();
            }

            if (empty($package->slug) && ! empty($package->name)) {
                $package->slug = Str::slug($package->name);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'session_count' => 'integer',
            'validity_days' => 'integer',
            'price_cents' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Clinic, $this>
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * @return HasMany<PatientPackage, $this>
     */
    public function patientPackages(): HasMany
    {
        return $this->hasMany(PatientPackage::class, 'package_id');
    }
}
