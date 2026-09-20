<?php

namespace App\Http\Resources;

use App\Models\Organization;
use App\Support\MarketingProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Organization
 */
class PublicOrganizationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'profile' => MarketingProfile::fromMeta($this->meta),
            'clinics' => PublicClinicResource::collection($this->whenLoaded('clinics')),
        ];
    }
}
