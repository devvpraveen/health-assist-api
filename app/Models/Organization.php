<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property string $name
 * @property string $slug
 * @property string $status
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'name',
    'slug',
    'description',
    'phone',
    'email',
    'website',
    'city',
    'state',
    'country',
    'status',
    'meta',
])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = [
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Organization $organization): void {
            if (empty($organization->uuid)) {
                $organization->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return HasMany<Branch, $this>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * @return HasMany<Clinic, $this>
     */
    public function clinics(): HasMany
    {
        return $this->hasMany(Clinic::class);
    }
}
