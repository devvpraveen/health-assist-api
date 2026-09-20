<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PatientDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
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
 * @property int|null $health_record_id
 * @property string|null $category
 * @property string $original_filename
 * @property string $mime_type
 * @property int $size
 * @property string $disk
 * @property string $path
 * @property string|null $checksum
 * @property string $visibility
 * @property int|null $uploaded_by
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
    'health_record_id',
    'category',
    'original_filename',
    'mime_type',
    'size',
    'disk',
    'path',
    'checksum',
    'visibility',
    'uploaded_by',
    'integrity_hash',
    'integrity_status',
    'integrity_provider',
    'integrity_proof_ref',
    'integrity_attested_at',
])]
#[Hidden(['path'])]
class PatientDocument extends Model
{
    /** @use HasFactory<PatientDocumentFactory> */
    use BelongsToTenant, HasFactory;

    public const ALLOWED_MIMES = ['pdf', 'jpg', 'jpeg', 'png'];

    public const MAX_SIZE_KB = 10240;

    protected $attributes = [
        'disk' => 'local',
        'visibility' => 'private',
        'integrity_status' => 'disabled',
    ];

    protected static function booted(): void
    {
        static::creating(function (PatientDocument $document): void {
            if (empty($document->uuid)) {
                $document->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
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
     * @return BelongsTo<HealthRecord, $this>
     */
    public function healthRecord(): BelongsTo
    {
        return $this->belongsTo(HealthRecord::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return HasMany<ReportAnalysis, $this>
     */
    public function reportAnalyses(): HasMany
    {
        return $this->hasMany(ReportAnalysis::class, 'document_id');
    }
}
