<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiLearningCandidate extends Model
{
    use BelongsToTenant;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_QUEUED = 'queued_for_dataset';

    protected $fillable = [
        'tenant_id',
        'agent',
        'status',
        'input_redacted',
        'original_output',
        'corrected_output',
        'deidentified',
        'feedback_id',
        'signal_id',
        'reviewed_by',
        'reviewed_at',
        'meta',
    ];

    protected $casts = [
        'deidentified' => 'boolean',
        'reviewed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(AiFeedback::class, 'feedback_id');
    }
}
