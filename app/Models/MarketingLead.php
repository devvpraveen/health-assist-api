<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MarketingLeadFactory;
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
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $source
 * @property string|null $campaign
 * @property string|null $provider_interest
 * @property string $status
 * @property int|null $appointment_id
 * @property Carbon|null $converted_at
 * @property array<string, mixed>|null $attribution
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'name',
    'email',
    'phone',
    'source',
    'campaign',
    'provider_interest',
    'status',
    'appointment_id',
    'converted_at',
    'attribution',
])]
class MarketingLead extends Model
{
    /** @use HasFactory<MarketingLeadFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_QUALIFIED = 'qualified';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_CONTACTED,
        self::STATUS_QUALIFIED,
        self::STATUS_CONVERTED,
        self::STATUS_CLOSED,
    ];

    protected $attributes = [
        'status' => self::STATUS_NEW,
    ];

    protected static function booted(): void
    {
        static::creating(function (MarketingLead $lead): void {
            if (empty($lead->uuid)) {
                $lead->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'converted_at' => 'datetime',
            'attribution' => 'array',
        ];
    }

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
