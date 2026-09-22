<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $key
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $category
 * @property string $version
 * @property string $status
 * @property array<int, string>|null $dependencies
 * @property array<int, string>|null $optional_dependencies
 * @property array<int, string>|null $conflicts
 * @property array<int, string>|null $capabilities
 * @property array<string, mixed>|null $configuration_schema
 * @property array<string, mixed>|null $settings_schema
 * @property array<string, mixed>|null $metadata
 * @property int $price_cents
 * @property string $currency
 * @property int|null $validity_days
 * @property bool $is_purchasable
 */
#[Fillable([
    'uuid',
    'key',
    'name',
    'slug',
    'description',
    'category',
    'version',
    'status',
    'price_cents',
    'currency',
    'validity_days',
    'is_purchasable',
    'dependencies',
    'optional_dependencies',
    'conflicts',
    'capabilities',
    'configuration_schema',
    'settings_schema',
    'metadata',
])]
class PlatformModule extends Model
{
    protected $table = 'modules';

    protected $attributes = [
        'category' => 'clinical',
        'version' => '1.0.0',
        'status' => 'active',
        'price_cents' => 0,
        'currency' => 'INR',
        'is_purchasable' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (PlatformModule $module): void {
            if (empty($module->uuid)) {
                $module->uuid = (string) Str::uuid();
            }
            if (empty($module->slug)) {
                $module->slug = Str::slug($module->key);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dependencies' => 'array',
            'optional_dependencies' => 'array',
            'conflicts' => 'array',
            'capabilities' => 'array',
            'configuration_schema' => 'array',
            'settings_schema' => 'array',
            'metadata' => 'array',
            'price_cents' => 'integer',
            'validity_days' => 'integer',
            'is_purchasable' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Package, $this>
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_modules', 'module_id', 'package_id')
            ->withPivot(['inclusion'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<TenantModule, $this>
     */
    public function tenantModules(): HasMany
    {
        return $this->hasMany(TenantModule::class, 'module_id');
    }
}
