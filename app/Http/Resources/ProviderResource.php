<?php

namespace App\Http\Resources;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Provider
 */
class ProviderResource extends JsonResource
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
            'tenant_uuid' => $this->relationLoaded('tenant')
                ? $this->tenant?->uuid
                : ($this->relationLoaded('clinic') && $this->clinic?->relationLoaded('tenant')
                    ? $this->clinic->tenant?->uuid
                    : null),
            'user_id' => $this->user_id,
            'clinic_id' => $this->clinic_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->display_name,
            'type' => $this->type,
            'bio' => $this->bio,
            'photo_url' => null,
            'years_experience' => $this->years_experience,
            'languages' => $this->languages,
            'license_number' => $this->license_number,
            'verification_status' => $this->verification_status,
            'is_public' => $this->is_public,
            'status' => $this->status,
            'specialties' => SpecialtyResource::collection($this->whenLoaded('specialties')),
            'branches' => BranchResource::collection($this->whenLoaded('branches')),
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
            'clinic' => new ClinicResource($this->whenLoaded('clinic')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
