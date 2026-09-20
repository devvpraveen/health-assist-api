<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ExperimentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $tenant_id
 * @property string $tenant_key
 * @property string $key
 * @property string $name
 * @property string $status
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'tenant_key',
    'key',
    'name',
    'status',
])]
class Experiment extends Model
{
    /** @use HasFactory<ExperimentFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_RUNNING = 'running';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_RUNNING,
        self::STATUS_PAUSED,
        self::STATUS_COMPLETED,
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'tenant_key' => 'system',
    ];

    protected static function booted(): void
    {
        static::creating(function (Experiment $experiment): void {
            if (empty($experiment->uuid)) {
                $experiment->uuid = (string) Str::uuid();
            }

            if (empty($experiment->tenant_key)) {
                $experiment->tenant_key = $experiment->tenant_id
                    ? 'tenant:'.$experiment->tenant_id
                    : 'system';
            }
        });
    }

    /** @return HasMany<ExperimentVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ExperimentVariant::class);
    }

    /** @return HasMany<ExperimentAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(ExperimentAssignment::class);
    }
}
