<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\WhatsAppAccountFactory;
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
 * @property int|null $clinic_id
 * @property string $name
 * @property string $instance_name
 * @property string|null $phone_number
 * @property string $status
 * @property string|null $webhook_secret
 * @property array<string, mixed>|null $settings
 * @property bool $is_active
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'clinic_id',
    'name',
    'instance_name',
    'phone_number',
    'status',
    'webhook_secret',
    'settings',
    'is_active',
])]
class WhatsAppAccount extends Model
{
    /** @use HasFactory<WhatsAppAccountFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'whatsapp_accounts';

    public const STATUS_DISCONNECTED = 'disconnected';

    public const STATUS_CONNECTING = 'connecting';

    public const STATUS_CONNECTED = 'connected';

    public const STATUS_ERROR = 'error';

    public const STATUSES = [
        self::STATUS_DISCONNECTED,
        self::STATUS_CONNECTING,
        self::STATUS_CONNECTED,
        self::STATUS_ERROR,
    ];

    protected $attributes = [
        'status' => self::STATUS_DISCONNECTED,
        'is_active' => true,
    ];

    protected $hidden = [
        'webhook_secret',
    ];

    protected static function booted(): void
    {
        static::creating(function (WhatsAppAccount $account): void {
            if (empty($account->uuid)) {
                $account->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
            'webhook_secret' => 'encrypted',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<Clinic, $this>
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * @return HasMany<WhatsAppConversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class, 'account_id');
    }

    /**
     * @return HasMany<WhatsAppMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'account_id');
    }
}
