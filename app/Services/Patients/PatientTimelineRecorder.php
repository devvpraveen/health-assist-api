<?php

namespace App\Services\Patients;

use App\Models\Patient;
use App\Models\PatientTimelineEvent;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PatientTimelineRecorder
{
    /**
     * @param  array<string, mixed>|null  $meta  Non-PHI identifiers only.
     */
    public function record(
        Patient $patient,
        string $eventType,
        string $title,
        ?string $description = null,
        ?Model $subject = null,
        ?array $meta = null,
        ?User $actor = null,
    ): PatientTimelineEvent {
        $actor ??= Auth::user();

        return PatientTimelineEvent::query()->create([
            'tenant_id' => TenantContext::id() ?? $patient->tenant_id,
            'patient_id' => $patient->id,
            'event_type' => $eventType,
            'title' => $title,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'occurred_at' => now(),
            'actor_user_id' => $actor?->id,
            'meta' => $meta,
        ]);
    }
}
