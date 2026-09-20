<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'module_id',
    'status',
    'source',
    'configuration',
    'activated_at',
    'deactivated_at',
])]
class TenantModule extends Model
{
    use BelongsToTenant;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISABLED = 'disabled';

    public const SOURCE_PACKAGE = 'package';

    public const SOURCE_ADDON = 'addon';

    public const SOURCE_MANUAL = 'manual';

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'source' => self::SOURCE_PACKAGE,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @return BelongsTo<PlatformModule, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(PlatformModule::class, 'module_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
