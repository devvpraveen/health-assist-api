<?php

namespace App\Models;

use Database\Factories\ReportAnalysisVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $report_analysis_id
 * @property int $version
 * @property string $kind
 * @property array<string, mixed>|null $payload
 * @property string|null $payload_text
 * @property int|null $created_by_user_id
 * @property string $source
 */
#[Fillable([
    'report_analysis_id',
    'version',
    'kind',
    'payload',
    'payload_text',
    'created_by_user_id',
    'source',
])]
class ReportAnalysisVersion extends Model
{
    /** @use HasFactory<ReportAnalysisVersionFactory> */
    use HasFactory;

    public const KIND_OCR = 'ocr';

    public const KIND_EXTRACTION = 'extraction';

    public const KIND_INTERPRETATION = 'interpretation';

    public const KIND_PATIENT_EXPLANATION = 'patient_explanation';

    public const KIND_FINAL = 'final';

    public const KINDS = [
        self::KIND_OCR,
        self::KIND_EXTRACTION,
        self::KIND_INTERPRETATION,
        self::KIND_PATIENT_EXPLANATION,
        self::KIND_FINAL,
    ];

    public const SOURCE_SYSTEM = 'system';

    public const SOURCE_AI = 'ai';

    public const SOURCE_CLINICIAN = 'clinician';

    protected $attributes = [
        'source' => self::SOURCE_SYSTEM,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'version' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ReportAnalysis, $this>
     */
    public function reportAnalysis(): BelongsTo
    {
        return $this->belongsTo(ReportAnalysis::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
