<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFeedback extends Model
{
    use BelongsToTenant;

    protected $table = 'ai_feedback';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'patient_id',
        'agent',
        'source',
        'rating',
        'helpful',
        'comment',
        'original_output',
        'corrected_output',
        'usage_record_id',
        'audit_log_id',
        'meta',
    ];

    protected $casts = [
        'helpful' => 'boolean',
        'meta' => 'array',
        'rating' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasCorrection(): bool
    {
        return filled($this->corrected_output)
            && $this->corrected_output !== $this->original_output;
    }
}
