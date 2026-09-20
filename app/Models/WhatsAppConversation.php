<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\WhatsAppConversationFactory;
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
 * @property int $account_id
 * @property int|null $patient_id
 * @property string $remote_phone
 * @property string|null $remote_jid
 * @property string $state
 * @property int|null $health_guide_conversation_id
 * @property string|null $handoff_summary
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'account_id',
    'patient_id',
    'remote_phone',
    'remote_jid',
    'state',
    'health_guide_conversation_id',
    'handoff_summary',
    'last_message_at',
])]
class WhatsAppConversation extends Model
{
    /** @use HasFactory<WhatsAppConversationFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'whatsapp_conversations';

    public const STATE_AI = 'ai';

    public const STATE_WAITING_HUMAN = 'waiting_human';

    public const STATE_HUMAN = 'human';

    public const STATE_CLOSED = 'closed';

    public const STATES = [
        self::STATE_AI,
        self::STATE_WAITING_HUMAN,
        self::STATE_HUMAN,
        self::STATE_CLOSED,
    ];

    protected $attributes = [
        'state' => self::STATE_AI,
    ];

    protected static function booted(): void
    {
        static::creating(function (WhatsAppConversation $conversation): void {
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
            'last_message_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isHumanManaged(): bool
    {
        return in_array($this->state, [self::STATE_WAITING_HUMAN, self::STATE_HUMAN], true);
    }

    /**
     * @return BelongsTo<WhatsAppAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<HealthGuideConversation, $this>
     */
    public function healthGuideConversation(): BelongsTo
    {
        return $this->belongsTo(HealthGuideConversation::class, 'health_guide_conversation_id');
    }

    /**
     * @return HasMany<WhatsAppMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'conversation_id');
    }

    /**
     * @return HasMany<WhatsAppHandoff, $this>
     */
    public function handoffs(): HasMany
    {
        return $this->hasMany(WhatsAppHandoff::class, 'conversation_id');
    }
}
