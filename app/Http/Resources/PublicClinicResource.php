<?php

namespace App\Http\Resources;

use App\Models\Clinic;
use App\Support\MarketingProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Clinic
 */
class PublicClinicResource extends JsonResource
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
            'experience_years' => $this->experience_years,
            'default_locale' => $this->default_locale,
            'supported_locales' => $this->supported_locales,
            'profile' => MarketingProfile::fromMeta($this->meta),
            'specialties' => SpecialtyResource::collection($this->whenLoaded('specialties')),
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'providers' => PublicProviderResource::collection($this->whenLoaded('providers')),
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
        ];
    }
}
