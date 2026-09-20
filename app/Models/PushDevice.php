<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PushDeviceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $user_id
 * @property string $token
 * @property string $platform
 * @property Carbon|null $last_seen_at
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'user_id',
    'token',
    'platform',
    'last_seen_at',
])]
class PushDevice extends Model
{
    /** @use HasFactory<PushDeviceFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = [
        'platform' => 'unknown',
    ];

    protected static function booted(): void
    {
        static::creating(function (PushDevice $device): void {
            if (empty($device->uuid)) {
                $device->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
