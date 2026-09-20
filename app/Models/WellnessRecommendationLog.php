<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\WellnessRecommendationLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $patient_id
 * @property int $content_id
 * @property float $score
 * @property array<string, mixed>|null $reason
 */
#[Fillable([
    'tenant_id',
    'patient_id',
    'content_id',
    'score',
    'reason',
    'created_at',
])]
class WellnessRecommendationLog extends Model
{
    /** @use HasFactory<WellnessRecommendationLogFactory> */
    use BelongsToTenant, HasFactory;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'float',
            'reason' => 'array',
            'created_at' => 'datetime',
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
     * @return BelongsTo<WellnessContent, $this>
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(WellnessContent::class, 'content_id');
    }
}
