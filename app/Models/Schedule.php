<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $provider_id
 * @property int|null $branch_id
 * @property int $clinic_id
 * @property int $day_of_week
 * @property string $start_time
 * @property string $end_time
 * @property int $slot_duration_minutes
 * @property bool $is_active
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'provider_id',
    'branch_id',
    'clinic_id',
    'day_of_week',
    'start_time',
    'end_time',
    'slot_duration_minutes',
    'is_active',
])]
class Schedule extends Model
{
    /** @use HasFactory<ScheduleFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = [
        'slot_duration_minutes' => 30,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (Schedule $schedule): void {
            if (empty($schedule->uuid)) {
                $schedule->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'slot_duration_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Clinic, $this>
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
