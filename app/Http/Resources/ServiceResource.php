<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Service
 */
class ServiceResource extends JsonResource
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
            'clinic_id' => $this->clinic_id,
            'specialty_id' => $this->specialty_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'price_cents' => $this->price_cents,
            'currency' => $this->currency,
            'is_public' => $this->is_public,
            'status' => $this->status,
            'specialty' => new SpecialtyResource($this->whenLoaded('specialty')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
