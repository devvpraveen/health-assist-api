<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\EmailWorkflowFactory;
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
 * @property string $trigger
 * @property bool $is_active
 * @property list<array<string, mixed>>|null $steps
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'tenant_key',
    'key',
    'name',
    'trigger',
    'is_active',
    'steps',
])]
class EmailWorkflow extends Model
{
    /** @use HasFactory<EmailWorkflowFactory> */
    use BelongsToTenant, HasFactory;

    public const TRIGGER_USER_REGISTERED = 'user_registered';

    public const TRIGGER_APPOINTMENT_BOOKED = 'appointment_booked';

    public const TRIGGER_LEAD_CREATED = 'lead_created';

    public const TRIGGERS = [
        self::TRIGGER_USER_REGISTERED,
        self::TRIGGER_APPOINTMENT_BOOKED,
        self::TRIGGER_LEAD_CREATED,
    ];

    protected $attributes = [
        'is_active' => true,
        'tenant_key' => 'system',
        'steps' => '[]',
    ];

    protected static function booted(): void
    {
        static::creating(function (EmailWorkflow $workflow): void {
            if (empty($workflow->uuid)) {
                $workflow->uuid = (string) Str::uuid();
            }
            if (empty($workflow->tenant_key)) {
                $workflow->tenant_key = $workflow->tenant_id
                    ? 'tenant:'.$workflow->tenant_id
                    : 'system';
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'steps' => 'array',
        ];
    }

    /** @return HasMany<EmailWorkflowRun, $this> */
    public function runs(): HasMany
    {
        return $this->hasMany(EmailWorkflowRun::class, 'workflow_id');
    }
}
