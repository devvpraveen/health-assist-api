<?php

namespace App\Http\Resources;

use App\Models\Clinic;
use App\Support\MarketingProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Clinic
 */
class ClinicResource extends JsonResource
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
            'organization_id' => $this->organization_id,
            'primary_branch_id' => $this->primary_branch_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo_url' => null,
            'type' => $this->type,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'whatsapp_number' => $this->whatsapp_number,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_public' => $this->is_public,
            'status' => $this->status,
            'experience_years' => $this->experience_years,
            'meta' => $this->meta,
            'profile' => MarketingProfile::fromMeta($this->meta),
            'default_locale' => $this->default_locale,
            'supported_locales' => $this->supported_locales,
            'specialties' => SpecialtyResource::collection($this->whenLoaded('specialties')),
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'providers' => ProviderResource::collection($this->whenLoaded('providers')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
