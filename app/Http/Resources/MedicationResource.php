<?php

namespace App\Http\Resources;

use App\Models\Medication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Medication
 */
class MedicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'patient_id' => $this->patient_id,
            'provider_id' => $this->provider_id,
            'name' => $this->name,
            'dosage' => $this->dosage,
            'frequency_label' => $this->frequency_label,
            'route' => $this->route,
            'instructions' => $this->instructions,
            'notes' => $this->notes,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'created_by_user_id' => $this->created_by_user_id,
            'schedules' => MedicationScheduleResource::collection($this->whenLoaded('schedules')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'disclaimer' => config('medication.disclaimer'),
        ];
    }
}
