<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $clinic_id
 * @property int|null $specialty_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $duration_minutes
 * @property int|null $price_cents
 * @property string $currency
 * @property bool $is_public
 * @property string $status
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'clinic_id',
    'specialty_id',
    'name',
    'slug',
    'description',
    'duration_minutes',
    'price_cents',
    'currency',
    'is_public',
    'status',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUSES = ['active', 'inactive', 'archived'];

    protected $attributes = [
        'duration_minutes' => 30,
        'currency' => 'INR',
        'is_public' => true,
        'status' => 'active',
    ];

    protected static function booted(): void
    {
        static::creating(function (Service $service): void {
            if (empty($service->uuid)) {
                $service->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'price_cents' => 'integer',
            'is_public' => 'boolean',
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
     * @return BelongsTo<Specialty, $this>
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }
}
