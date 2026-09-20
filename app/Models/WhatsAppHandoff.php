<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $conversation_id
 * @property string $requested_by
 * @property string $status
 * @property string|null $urgency
 * @property string|null $summary
 * @property int|null $assigned_user_id
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'conversation_id',
    'requested_by',
    'status',
    'urgency',
    'summary',
    'assigned_user_id',
])]
class WhatsAppHandoff extends Model
{
    use BelongsToTenant;

    protected $table = 'whatsapp_handoffs';

    public const REQUESTED_BY_PATIENT = 'patient';

    public const REQUESTED_BY_SYSTEM = 'system';

    public const REQUESTED_BY_STAFF = 'staff';

    public const STATUS_OPEN = 'open';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CANCELLED = 'cancelled';

    protected $attributes = [
        'status' => self::STATUS_OPEN,
        'requested_by' => self::REQUESTED_BY_PATIENT,
    ];

    protected static function booted(): void
    {
        static::creating(function (WhatsAppHandoff $handoff): void {
            if (empty($handoff->uuid)) {
                $handoff->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<WhatsAppConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
