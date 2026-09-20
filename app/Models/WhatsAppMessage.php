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
 * @property int $conversation_id
 * @property int $tenant_id
 * @property int $account_id
 * @property string $direction
 * @property string $type
 * @property string|null $body
 * @property string|null $evolution_message_id
 * @property array<string, mixed>|null $payload
 * @property string $status
 */
#[Fillable([
    'uuid',
    'conversation_id',
    'tenant_id',
    'account_id',
    'direction',
    'type',
    'body',
    'evolution_message_id',
    'payload',
    'status',
])]
class WhatsAppMessage extends Model
{
    use BelongsToTenant;

    protected $table = 'whatsapp_messages';

    public const DIRECTION_INBOUND = 'inbound';

    public const DIRECTION_OUTBOUND = 'outbound';

    public const TYPE_TEXT = 'text';

    public const TYPE_IMAGE = 'image';

    public const TYPE_DOCUMENT = 'document';

    public const TYPE_AUDIO = 'audio';

    public const TYPE_LOCATION = 'location';

    public const TYPE_OTHER = 'other';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    protected $attributes = [
        'type' => self::TYPE_TEXT,
        'status' => self::STATUS_RECEIVED,
    ];

    protected static function booted(): void
    {
        static::creating(function (WhatsAppMessage $message): void {
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
            'payload' => 'array',
        ];
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
     * @return BelongsTo<WhatsAppAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }
}
