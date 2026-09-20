<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $patient_id
 * @property int|null $user_id
 * @property int|null $guest_session_id
 * @property string $status
 * @property string|null $locale
 * @property array<string, mixed>|null $structured_state
 * @property string|null $safety_level
 * @property Carbon|null $last_message_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'patient_id',
    'user_id',
    'guest_session_id',
    'status',
    'locale',
    'structured_state',
    'safety_level',
    'last_message_at',
])]
class HealthGuideConversation extends Model
{
    use BelongsToTenant;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ESCALATED = 'escalated';

    public const STATUS_ABANDONED = 'abandoned';

    public const STATUS_AWAITING_AUTH = 'awaiting_auth';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_COMPLETED,
        self::STATUS_ESCALATED,
        self::STATUS_ABANDONED,
        self::STATUS_AWAITING_AUTH,
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected static function booted(): void
    {
        static::creating(function (HealthGuideConversation $conversation): void {
            if (empty($conversation->uuid)) {
                $conversation->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'structured_state' => 'array',
            'last_message_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<GuestSession, $this>
     */
    public function guestSession(): BelongsTo
    {
        return $this->belongsTo(GuestSession::class, 'guest_session_id');
    }

    /**
     * @return HasMany<HealthGuideMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(HealthGuideMessage::class, 'conversation_id');
    }

    /**
     * @return HasMany<SafetyAssessment, $this>
     */
    public function safetyAssessments(): HasMany
    {
        return $this->hasMany(SafetyAssessment::class, 'conversation_id');
    }
}
