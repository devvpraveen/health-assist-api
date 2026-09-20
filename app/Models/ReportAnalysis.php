<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ReportAnalysisFactory;
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
 * @property int $document_id
 * @property int|null $health_record_id
 * @property string $status
 * @property string|null $report_type
 * @property string|null $ocr_provider
 * @property string|null $ocr_raw_text
 * @property array<string, mixed>|null $extracted_facts
 * @property array<string, mixed>|null $interpretation
 * @property array<int, mixed>|null $reference_range_findings
 * @property string|null $safety_level
 * @property int|null $safety_assessment_id
 * @property string|null $patient_explanation
 * @property string|null $clinician_notes
 * @property int|null $reviewed_by_user_id
 * @property Carbon|null $reviewed_at
 * @property int|null $ai_usage_record_id
 * @property string|null $error_message
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'patient_id',
    'document_id',
    'health_record_id',
    'status',
    'report_type',
    'ocr_provider',
    'ocr_raw_text',
    'extracted_facts',
    'interpretation',
    'reference_range_findings',
    'safety_level',
    'safety_assessment_id',
    'patient_explanation',
    'clinician_notes',
    'reviewed_by_user_id',
    'reviewed_at',
    'ai_usage_record_id',
    'error_message',
])]
class ReportAnalysis extends Model
{
    /** @use HasFactory<ReportAnalysisFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_AWAITING_REVIEW = 'awaiting_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_QUEUED,
        self::STATUS_PROCESSING,
        self::STATUS_AWAITING_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_FAILED,
    ];

    public const REPORT_TYPES = ['lab', 'imaging', 'prescription', 'other'];

    protected $attributes = [
        'status' => self::STATUS_QUEUED,
    ];

    protected static function booted(): void
    {
        static::creating(function (ReportAnalysis $analysis): void {
            if (empty($analysis->uuid)) {
                $analysis->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'extracted_facts' => 'array',
            'interpretation' => 'array',
            'reference_range_findings' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function isEditableByClinician(): bool
    {
        return $this->status === self::STATUS_AWAITING_REVIEW;
    }

    public function isLocked(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED], true);
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<PatientDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(PatientDocument::class, 'document_id');
    }

    /**
     * @return BelongsTo<HealthRecord, $this>
     */
    public function healthRecord(): BelongsTo
    {
        return $this->belongsTo(HealthRecord::class);
    }

    /**
     * @return BelongsTo<SafetyAssessment, $this>
     */
    public function safetyAssessment(): BelongsTo
    {
        return $this->belongsTo(SafetyAssessment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * @return BelongsTo<AiUsageRecord, $this>
     */
    public function aiUsageRecord(): BelongsTo
    {
        return $this->belongsTo(AiUsageRecord::class, 'ai_usage_record_id');
    }

    /**
     * @return HasMany<ReportAnalysisVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ReportAnalysisVersion::class);
    }
}
