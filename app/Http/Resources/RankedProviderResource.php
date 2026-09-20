<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RankedProviderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = is_array($this->resource) ? $this->resource : (array) $this->resource;

        return [
            'id' => $item['id'] ?? null,
            'uuid' => $item['uuid'] ?? null,
            'display_name' => $item['display_name'] ?? null,
            'type' => $item['type'] ?? null,
            'clinic_id' => $item['clinic_id'] ?? null,
            'clinic_name' => $item['clinic_name'] ?? null,
            'city' => $item['city'] ?? null,
            'years_experience' => $item['years_experience'] ?? null,
            'verification_status' => $item['verification_status'] ?? null,
            'is_public' => $item['is_public'] ?? null,
            'specialties' => $item['specialties'] ?? [],
            'score' => $item['score'] ?? 0,
            'match_reason' => $item['match_reason'] ?? [],
            'specialty_match' => $item['specialty_match'] ?? false,
            'condition_match' => $item['condition_match'] ?? false,
            'availability' => $item['availability'] ?? false,
            'distance' => $item['distance'] ?? null,
            'next_slots' => $item['next_slots'] ?? [],
            'explanation' => $item['explanation'] ?? null,
        ];
    }
}
