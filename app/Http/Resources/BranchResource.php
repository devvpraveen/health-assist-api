<?php

namespace App\Http\Resources;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Branch
 */
class BranchResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'timezone' => $this->timezone,
            'status' => $this->status,
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
