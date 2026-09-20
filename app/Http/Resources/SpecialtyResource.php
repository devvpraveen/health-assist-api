<?php

namespace App\Http\Resources;

use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Specialty
 */
class SpecialtyResource extends JsonResource
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
            'status' => $this->status,
            'is_system' => $this->tenant_id === null,
            'is_primary' => $this->whenPivotLoaded('provider_specialty', fn () => (bool) $this->pivot->is_primary),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
