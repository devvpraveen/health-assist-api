<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiLearningSignal extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    public const TYPES = [
        'patient_helpful',
        'patient_not_helpful',
        'clinician_corrected',
        'clinician_approved',
        'clinician_rejected',
        'appointment_booked',
        'appointment_cancelled',
        'treatment_completed',
        'followup_completed',
        'patient_improved',
        'patient_worsened',
        'provider_selected',
        'provider_changed',
    ];

    protected $fillable = [
        'tenant_id',
        'actor_user_id',
        'patient_id',
        'agent',
        'signal_type',
        'source',
        'usage_record_id',
        'audit_log_id',
        'subject_type',
        'subject_id',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AiLearningSignal $signal): void {
            if ($signal->created_at === null) {
                $signal->created_at = now();
            }
        });
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
