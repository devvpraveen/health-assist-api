<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $account_id
 * @property int|null $tenant_id
 * @property string $event
 * @property array<string, mixed> $payload
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property string|null $error
 */
#[Fillable([
    'uuid',
    'account_id',
    'tenant_id',
    'event',
    'payload',
    'processed_at',
    'error',
])]
class WhatsAppWebhookEvent extends Model
{
    public $timestamps = false;

    protected $table = 'whatsapp_webhook_events';

    protected $attributes = [];

    protected static function booted(): void
    {
        static::creating(function (WhatsAppWebhookEvent $event): void {
            if (empty($event->uuid)) {
                $event->uuid = (string) Str::uuid();
            }

            if ($event->created_at === null) {
                $event->created_at = now();
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
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WhatsAppAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'account_id');
    }
}
