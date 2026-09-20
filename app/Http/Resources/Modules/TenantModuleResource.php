<?php

namespace App\Http\Resources\Modules;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TenantModule */
class TenantModuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'source' => $this->source,
            'configuration' => $this->configuration,
            'activated_at' => $this->activated_at,
            'deactivated_at' => $this->deactivated_at,
            'module' => $this->whenLoaded('module', fn () => new PlatformModuleResource($this->module)),
        ];
    }
}
