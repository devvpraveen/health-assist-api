<?php

namespace App\Http\Resources;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Provider
 */
class PublicProviderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->display_name,
            'type' => $this->type,
            'bio' => $this->bio,
            'photo_url' => null,
            'years_experience' => $this->years_experience,
            'languages' => $this->languages,
            'clinic' => $this->whenLoaded('clinic', fn () => [
                'uuid' => $this->clinic->uuid,
                'name' => $this->clinic->name,
                'slug' => $this->clinic->slug,
                'city' => $this->clinic->city,
            ]),
            'specialties' => SpecialtyResource::collection($this->whenLoaded('specialties')),
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
        ];
    }
}
