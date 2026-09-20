<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\HealthRecordFactory;
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
 * @property int $patient_id
 * @property string $category
 * @property string $title
 * @property string|null $description
 * @property Carbon|null $recorded_at
 * @property string $status
 * @property string|null $integrity_hash
 * @property string $integrity_status
 * @property string|null $integrity_provider
 * @property string|null $integrity_proof_ref
 * @property Carbon|null $integrity_attested_at
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'patient_id',
    'category',
    'title',
    'description',
    'recorded_at',
    'status',
    'integrity_hash',
    'integrity_status',
    'integrity_provider',
    'integrity_proof_ref',
    'integrity_attested_at',
])]
class HealthRecord extends Model
{
    /** @use HasFactory<HealthRecordFactory> */
    use BelongsToTenant, HasFactory;

    public const CATEGORIES = [
        'laboratory',
        'prescription',
        'imaging',
        'consultation_note',
        'discharge',
        'treatment',
        'referral',
        'vaccination',
        'medication_history',
        'wellness',
        'other',
    ];

    public const STATUSES = ['active', 'archived', 'void'];

    protected $attributes = [
        'status' => 'active',
        'integrity_status' => 'disabled',
    ];

    protected static function booted(): void
    {
        static::creating(function (HealthRecord $record): void {
            if (empty($record->uuid)) {
                $record->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'integrity_attested_at' => 'datetime',
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
     * @return HasMany<PatientDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(PatientDocument::class);
    }
}
