<?php

namespace App\Http\Resources;

use App\Models\Organization;
use App\Support\MarketingProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Organization
 */
class OrganizationResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'status' => $this->status,
            'meta' => $this->meta,
            'profile' => MarketingProfile::fromMeta($this->meta),
            'clinics' => ClinicResource::collection($this->whenLoaded('clinics')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
