<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $conversation_id
 * @property int|null $patient_id
 * @property string|null $input_category
 * @property string|null $input_redacted
 * @property string $level
 * @property array<int, string>|null $matched_rule_codes
 * @property string|null $action
 * @property array<string, mixed>|null $rule_version_snapshot
 * @property string|null $model_hint
 * @property int|null $reviewer_user_id
 * @property Carbon|null $created_at
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'conversation_id',
    'patient_id',
    'input_category',
    'input_redacted',
    'level',
    'matched_rule_codes',
    'action',
    'rule_version_snapshot',
    'model_hint',
    'reviewer_user_id',
    'created_at',
])]
class SafetyAssessment extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    public const LEVEL_EMERGENCY = 'emergency';

    public const LEVEL_URGENT = 'urgent';

    public const LEVEL_CLINICIAN_REVIEW = 'clinician_review';

    public const LEVEL_ROUTINE = 'routine';

    public const LEVEL_INSUFFICIENT_INFORMATION = 'insufficient_information';

    public const LEVELS = [
        self::LEVEL_EMERGENCY,
        self::LEVEL_URGENT,
        self::LEVEL_CLINICIAN_REVIEW,
        self::LEVEL_ROUTINE,
        self::LEVEL_INSUFFICIENT_INFORMATION,
    ];

    protected static function booted(): void
    {
        static::creating(function (SafetyAssessment $assessment): void {
            if (empty($assessment->uuid)) {
                $assessment->uuid = (string) Str::uuid();
            }
            if ($assessment->created_at === null) {
                $assessment->created_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'matched_rule_codes' => 'array',
            'rule_version_snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<HealthGuideConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(HealthGuideConversation::class, 'conversation_id');
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
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
