<?php

namespace App\Http\Resources;

use App\Models\PatientTimelineEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PatientTimelineEvent
 */
class PatientTimelineEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'patient_id' => $this->patient_id,
            'event_type' => $this->event_type,
            'title' => $this->title,
            'description' => $this->description,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'occurred_at' => $this->occurred_at,
            'actor_user_id' => $this->actor_user_id,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
        ];
    }
}
