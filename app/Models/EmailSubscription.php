<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\EmailSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property string $email
 * @property int|null $user_id
 * @property Carbon|null $consented_at
 * @property Carbon|null $unsubscribed_at
 * @property string $unsubscribe_token
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'email',
    'user_id',
    'consented_at',
    'unsubscribed_at',
    'unsubscribe_token',
])]
class EmailSubscription extends Model
{
    /** @use HasFactory<EmailSubscriptionFactory> */
    use BelongsToTenant, HasFactory;

    protected static function booted(): void
    {
        static::creating(function (EmailSubscription $subscription): void {
            if (empty($subscription->uuid)) {
                $subscription->uuid = (string) Str::uuid();
            }
            if (empty($subscription->unsubscribe_token)) {
                $subscription->unsubscribe_token = Str::random(48);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function isSubscribed(): bool
    {
        return $this->consented_at !== null && $this->unsubscribed_at === null;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<EmailWorkflowRun, $this> */
    public function workflowRuns(): HasMany
    {
        return $this->hasMany(EmailWorkflowRun::class, 'subscription_id');
    }
}
