<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MedicationScheduleFactory;
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
 * @property int $medication_id
 * @property string $time_of_day
 * @property string $timezone
 * @property list<int>|null $days_of_week
 * @property bool $is_active
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'medication_id',
    'time_of_day',
    'timezone',
    'days_of_week',
    'is_active',
])]
class MedicationSchedule extends Model
{
    /** @use HasFactory<MedicationScheduleFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = [
        'timezone' => 'UTC',
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (MedicationSchedule $schedule): void {
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
            'days_of_week' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Medication, $this>
     */
    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    /**
     * @return HasMany<MedicationLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(MedicationLog::class, 'schedule_id');
    }

    /**
     * @return HasMany<MedicationReminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(MedicationReminder::class, 'schedule_id');
    }
}
