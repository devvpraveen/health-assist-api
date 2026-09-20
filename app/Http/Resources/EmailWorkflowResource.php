<?php

namespace App\Http\Resources;

use App\Models\EmailWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EmailWorkflow */
class EmailWorkflowResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'tenant_key' => $this->tenant_key,
            'key' => $this->key,
            'name' => $this->name,
            'trigger' => $this->trigger,
            'is_active' => $this->is_active,
            'steps' => $this->steps ?? [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
