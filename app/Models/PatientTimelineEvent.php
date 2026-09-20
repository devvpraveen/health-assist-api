<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PatientTimelineEventFactory;
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
 * @property int $patient_id
 * @property string $event_type
 * @property string $title
 * @property string|null $description
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property Carbon $occurred_at
 * @property int|null $actor_user_id
 * @property array<string, mixed>|null $meta
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'patient_id',
    'event_type',
    'title',
    'description',
    'subject_type',
    'subject_id',
    'occurred_at',
    'actor_user_id',
    'meta',
])]
class PatientTimelineEvent extends Model
{
    /** @use HasFactory<PatientTimelineEventFactory> */
    use BelongsToTenant, HasFactory;

    protected static function booted(): void
    {
        static::creating(function (PatientTimelineEvent $event): void {
            if (empty($event->uuid)) {
                $event->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
