<?php

namespace App\Http\Resources;

use App\Models\AppointmentWaitlistEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AppointmentWaitlistEntry
 */
class AppointmentWaitlistEntryResource extends JsonResource
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
            'provider_id' => $this->provider_id,
            'clinic_id' => $this->clinic_id,
            'service_id' => $this->service_id,
            'preferred_date' => $this->preferred_date?->toDateString(),
            'notes' => $this->notes,
            'status' => $this->status,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'provider' => new ProviderResource($this->whenLoaded('provider')),
            'clinic' => new ClinicResource($this->whenLoaded('clinic')),
            'service' => new ServiceResource($this->whenLoaded('service')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
