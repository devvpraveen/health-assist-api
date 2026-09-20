<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $conversation_id
 * @property int $tenant_id
 * @property string $role
 * @property string $content
 * @property array<string, mixed>|null $meta
 * @property int|null $ai_usage_record_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'uuid',
    'conversation_id',
    'tenant_id',
    'role',
    'content',
    'meta',
    'ai_usage_record_id',
])]
class HealthGuideMessage extends Model
{
    use BelongsToTenant;

    public const ROLE_USER = 'user';

    public const ROLE_ASSISTANT = 'assistant';

    public const ROLE_SYSTEM = 'system';

    public const ROLES = [
        self::ROLE_USER,
        self::ROLE_ASSISTANT,
        self::ROLE_SYSTEM,
    ];

    protected static function booted(): void
    {
        static::creating(function (HealthGuideMessage $message): void {
            if (empty($message->uuid)) {
                $message->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<HealthGuideConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(HealthGuideConversation::class, 'conversation_id');
    }

    /**
     * @return BelongsTo<AiUsageRecord, $this>
     */
    public function aiUsageRecord(): BelongsTo
    {
        return $this->belongsTo(AiUsageRecord::class, 'ai_usage_record_id');
    }
}
