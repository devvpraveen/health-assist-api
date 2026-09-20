<?php

namespace App\Http\Resources;

use App\Models\MedicationLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MedicationLog
 */
class MedicationLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'medication_id' => $this->medication_id,
            'patient_id' => $this->patient_id,
            'schedule_id' => $this->schedule_id,
            'scheduled_for' => $this->scheduled_for,
            'logged_at' => $this->logged_at,
            'status' => $this->status,
            'notes' => $this->notes,
            'logged_by_user_id' => $this->logged_by_user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
